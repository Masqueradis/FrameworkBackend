<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $resultDTO = $this->authService->register($request->toDTO());

        return $this->respondSuccess(
            data: [
                'user' => new UserResource($resultDTO->user),
                'token' => $resultDTO->accessToken,
            ],
            message: 'Registered successfully.',
            code: Response::HTTP_CREATED,
        );
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $loginDTO = $request->toDTO();
        $resultDTO = $this->authService->login($loginDTO);

        return $this->respondSuccess(
            data: [
                'user' => new UserResource($resultDTO->user),
                'token' => $resultDTO->accessToken,
            ],
            message: 'Login successfully.',
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authService->logout($user);

        return $this->respondSuccess(
            message: 'Logged out successfully.',
        );
    }
}
