<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\User\LoginUserDTO;
use App\DTO\User\RegisterUserDTO;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

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
    public function register(RegisterUserDTO $request): JsonResponse|RedirectResponse
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

    #[OA\Get(
        path: '/api/v1/verify/{token}',
        description: 'Verifies the user\'s email using the token provided in the registration email. On success, it returns the user object and an access token.',
        summary: 'Verify user email',
        tags: ['Auth'],
        parameters: [
            new OA\Parameter(
                name: 'token',
                description: 'Unique verification token sent to the user\'s email',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'a1b2c3d4e5f6g7h8i9j0')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email verified successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Email verified and registered successfully.'),
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
                                    example: '1|laravel_sanctum_token_xyz123'
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request (invalid or expired token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'The link is expired or invalid. Please register again.')
                    ]
                )
            )
        ]
    )]
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
    public function login(LoginUserDTO $request): JsonResponse|RedirectResponse
    {
        $resultData = $this->authService->login($request);

        if ($resultData->user->google2fa_secret) {
            if (request()->expectsJson()) {
                return $this->respondSuccess(
                    data: ['require_2fa' => true, 'user_id' => $resultData->user->id],
                    message: 'Two factor authentication was successful.',
                );
            }

            request()->session()->put('2fa:user_id', $resultData->user->id);
            return redirect()->route('login.2fa');
        }

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

    #[OA\Post(
        path: '/api/v1/email/verification-notification',
        description: 'Sends a new email verification link to the authenticated user.',
        summary: 'Resend verification email',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification link sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Verification link sent!')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized (missing or invalid token)'
            ),
            new OA\Response(
                response: 429,
                description: 'Too Many Requests (rate limit exceeded for sending emails)'
            )
        ]
    )]
    public function resendVerificationEmail(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $user?->sendEmailVerificationNotification();

        return back()->with('message', 'Verification link sent!');
    }

    #[OA\Get(
        path: '/api/v1/user',
        description: 'Returns the profile details of the currently authenticated user based on the provided Bearer token.',
        summary: 'Get authenticated user details',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', example: '2026-05-31T15:00:00Z', nullable: true),
                        new OA\Property(property: 'avatar_path', type: 'string', example: 'avatars/file123.jpg', nullable: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-01T10:00:00Z'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-05-31T12:00:00Z')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized (missing or invalid token)'
            )
        ]
    )]
    public function user(Request $request): JsonResponse|RedirectResponse
    {
        return response()->json($request->user());
    }
}
