<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    private Google2FA $google2fa;

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQrCodeUrl(string $name, string $email, string $secret): string
    {
        return $this->google2fa->getQrCodeUrl(
            $name,
            $email,
            $secret,
        );
    }

    public function verify(string $secret, string $otp): bool|int
    {
        return $this->google2fa->verifyKey($secret, $otp);
    }

    public function enable2fa(User $user, string $secret, string $otp): bool
    {
        $isValid = $this->verify($secret, $otp);

        if ($isValid) {
            $this->userRepository->update2faSecret($user->id, $secret);
            return true;
        }

        return false;
    }

    public function disable2fa(User $user): void
    {
        $this->userRepository->update2faSecret($user->id, null);
    }

    public function verifyLogin(int $userId, string $otp): bool
    {
        $user = User::find($userId);

        if (!$user || !$user->google2fa_secret) {
            return false;
        }

        if ($this->verify($user->google2fa_secret, $otp)) {
            Auth::login($user);
            return true;
        }

        return false;
    }
}
