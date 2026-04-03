<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\AuthResultData;
use App\Data\LoginUserData;
use App\Data\RegisterUserData;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use App\Models\User;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function register(RegisterUserData $dto): AuthResultData
    {
        $userData = [
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ];

        $user = $this->userRepository->create($userData);

        event(new Registered($user));

        $token = $user->createToken('ApiAccess')->accessToken;

        return new AuthResultData($user, $token);
    }

    public function login(LoginUserData $dto): AuthResultData
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (!Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $token = $user->createToken('ApiAccess')->accessToken;

        return new AuthResultData($user, $token);
    }

    public function logout(User $user): void
    {
        $token = $user->token();
        if ($token instanceof \Laravel\Passport\Token) {
            $token->revoke();
        }
    }
}
