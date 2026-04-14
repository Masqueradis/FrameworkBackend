<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\AuthResultData;
use App\Data\LoginUserData;
use App\Data\RegisterUserData;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use App\Models\User;

readonly class AuthService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function register(RegisterUserData $data): void
    {
        $oldToken = Cache::get('pending_email_'. $data->email);
        if($oldToken){
            Cache::forget('pending_email_'. $oldToken);
        }

        $token = Str::random(60);

        Cache::put('pending_email_'. $data->email, $token, now()->addMinutes(30));
        Cache::put('pending_email_'. $token, [
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ], now()->addMinutes(30));

        $verifyUrl = route('verification.verify.custom', ['token' => $token]);

        Mail::raw("Verify email, link: {$verifyUrl} ", function ($message) use ($data) {
            $message->to($data->email)
                ->subject('Verify your email address');
        });
    }

    public function verifyRegistration(string $token): AuthResultData
    {
        $userData = Cache::get('pending_email_'. $token);

        if(!$userData){
            throw ValidationException::withMessages([
                'token' => ['Link invalid or expired.'],
            ]);
        }

        $user = $this->userRepository->create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'email_verified_at' => now(),
        ]);

        $this-> userRepository->assignRole($user, 'customer');

        Cache::forget('pending_email_' . $token);
        Cache::forget('pending_email_' . $userData['email']);

        $apiToken = $user->createToken('ApiAccess')->accessToken;

        return new AuthResultData($user, $apiToken);
    }

    public function login(LoginUserData $data): AuthResultData
    {
        $user = $this->userRepository->findByEmail($data->email);

        if (!$user || !Hash::check($data->password, $user->password)) {
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
