<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\User\VerifyTwoFactorDTO;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorController extends ApiController
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorAuthService
    ) {}

    public function generate(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $secret = $this->twoFactorAuthService->generateSecret();

        $qrCodeUrl = $this->twoFactorAuthService->getQrCodeUrl(
            config('app.name'),
            (string) $user?->email,
            $secret,
        );

        $request->session()->put('2fa_secret', $secret);
        $request->session()->put('2fa_qr', $qrCodeUrl);

        return back();
    }

    public function enable(Request $request, VerifyTwoFactorDTO $dto): RedirectResponse
    {
        $secret = $request->session()->get('2fa_secret');

        if (! $secret) {
            return back()->withErrors(['otp' => 'Session expired. Please generate a new QR code.']);
        }

        $user = auth()->user();
        assert($user instanceof User);

        $codes = $this->twoFactorAuthService->enable2fa($user, $secret, $dto->otp);

        if ($codes) {
            $request->session()->forget(['2fa_secret', '2fa_qr']);

            return back()
                ->with('success', 'Two factor authentication successfully enabled.')
                ->with('recovery_codes', $codes);
        }

        return back()->withErrors(['otp' => 'Invalid verification code. Please try again.']);
    }

    public function disable(): RedirectResponse
    {
        $user = auth()->user();
        assert($user instanceof User);

        $this->twoFactorAuthService->disable2fa($user);

        return back()->with('success', 'Two factor authentication successfully disabled.');
    }

    public function showVerifyForm(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.2fa');
    }

    #[OA\Post(
        path: '/api/login/2fa',
        description: 'Verifies the 2FA code (or recovery code) using the user_id returned from the initial login step. On success, returns the user object and access token.',
        summary: 'Verify 2FA login',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'otp'],
                properties: [
                    new OA\Property(property: 'user_id', description: 'The ID of the user attempting to log in', type: 'integer', example: 1),
                    new OA\Property(property: 'otp', description: 'The 6-digit authenticator code or 10-character recovery code', type: 'string', example: '123456'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Two factor authentication successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Two factor authentication successful.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'user',
                                    description: 'User details',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'token',
                                    description: 'Authentication token (Bearer)',
                                    type: 'string',
                                    example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Missing User ID',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'User ID is required.'),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Invalid OTP',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid verification code.'),
                    ]
                )
            ),
        ]
    )]
    public function verifyLogin(Request $request, VerifyTwoFactorDTO $dto): JsonResponse|RedirectResponse
    {
        $userId = request()->expectsJson()
            ? $request->input('user_id')
            : $request->session()->get('2fa:user_id');

        if (! $userId) {
            if (request()->expectsJson()) {
                return $this->respondError('User ID is required.', Response::HTTP_BAD_REQUEST);
            }

            return redirect()->route('login');
        }

        if ($this->twoFactorAuthService->verifyLogin((int) $userId, $dto->otp)) {

            if (request()->expectsJson()) {
                /** @var User $user */
                $user = User::findOrFail((int) $userId);

                $token = $user->createToken('ApiAccess')->accessToken;

                return $this->respondSuccess(
                    data: [
                        'user' => new UserResource($user),
                        'token' => $token,
                    ],
                    message: 'Two factor authentication successful.'
                );
            }

            $request->session()->forget('2fa:user_id');

            return redirect()->intended('/profile');
        }

        if (request()->expectsJson()) {
            return $this->respondError('Invalid verification code.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return back()->withErrors(['otp' => 'Invalid verification code. Please try again.']);
    }
}
