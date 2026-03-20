<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\RegisterUserDTO;
use App\DTO\LoginUserDTO;
use App\DTO\AuthResultDTO;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function register(RegisterUserDTO $dto): AuthResultDTO
    {
        $userData = [
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ];

        $user = $this->userRepository->create($userData);
        $token = $user->createToken('ApiAccess')->accessToken;

        return new AuthResultDTO($user, $token);
    }

    public function login(LoginUserDTO $dto): AuthResultDTO
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (!Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $token = $user->createToken('ApiAccess')->accessToken;

        return new AuthResultDTO($user, $token);
    }

    public function logout(User $user): void
    {
        $token = $user->token();
        if ($token instanceof \Laravel\Passport\Token) {
            $token->revoke();
        }
    }
}
