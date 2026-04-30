<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\LoginUserData;
use App\Data\RegisterUserData;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function showRegisterForm(): View
    {
        return view('auth.register');
    }
    #[OA\Post(
        path: '/api/register',
        description: 'Request email and password, return user-object and token',
        summary: 'Register user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret_password'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successfully register',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully register'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Wrong validation'
            ),
        ]
    )]
    public function register(RegisterUserData $request): JsonResponse|RedirectResponse
    {
        $this->authService->register($request);

        if (request()->expectsJson()) {
            return $this->respondSuccess(
                data: [],
                message: 'Registration pending. Check your email to activate your account.',
                code: Response::HTTP_ACCEPTED,
            );
        }

        return redirect()->route('login')
            ->with('status', 'A verification link has been sent to your email. It is valid for 30 minutes.');
    }

    public function verifyEmail(string $token): JsonResponse|RedirectResponse
    {
        try {
            $resultData = $this->authService->verifyRegistration($token);
        } catch (ValidationException $e) {
            if (request()->expectsJson()) {
                return $this->respondError($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            return redirect()->route('register')
                ->withErrors(['email' => 'The link is expired or invalid. Please register again.']);
        }

        if (request()->expectsJson()) {
            return $this->respondSuccess(
                data: [
                    'user' => new UserResource($resultData->user),
                    'token' => $resultData->accessToken,
                ],
                message: 'Email verified and registered successfully.',
            );
        }

        Auth::login($resultData->user);
        request()->session()->regenerate();

        return redirect()->intended('/profile')->with('status', 'Email verified. Welcome');
    }

    #[OA\Post(
        path: '/api/login',
        description: 'Request email and password, return user-object and token',
        summary: 'Login user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret_password'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successfully login',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully login'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Wrong validation'
            ),
        ]
    )]
    public function login(LoginUserData $request): JsonResponse|RedirectResponse
    {
        $resultData = $this->authService->login($request);

        if (request()->expectsJson()) {
            return $this->respondSuccess(
                data: [
                    'user' => new UserResource($resultData->user),
                    'token' => $resultData->accessToken,
                ],
                message: 'Login successfully.',
            );
        }

        Auth::login($resultData->user);
        request()->session()->regenerate();

        return redirect()->intended('/profile');
    }

    #[OA\Post(
        path: '/api/logout',
        description: 'Request user logout',
        summary: 'Logout user',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successfully logout',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
                    ]
                )
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        $this->authService->logout($user);
        if ($request->expectsJson()) {
            return $this->respondSuccess(
                message: 'Logged out successfully.',
            );
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function showVerificationNotice(): View
    {
        return view('auth.verify-email');
    }

    public function resendVerificationEmail(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $user?->sendEmailVerificationNotification();

        return back()->with('message', 'Verification link sent!');
    }

    public function user(Request $request): JsonResponse|RedirectResponse
    {
        return response()->json($request->user());
    }
}
