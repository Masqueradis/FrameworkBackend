<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    private Google2FA $google2fa;

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
        $this->google2fa = new Google2FA;
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

    public function enable2fa(User $user, string $secret, string $otp): array|false
    {
        if ($this->verify($secret, $otp)) {
            $plainCodes = [];
            $hashedCodes = [];

            for ($i = 0; $i < 8; $i++) {
                $code = Str::random(10);
                $plainCodes[] = $code;
                $hashedCodes[] = Hash::make($code);
            }

            $this->userRepository->update2faSecret($user->id, $secret, json_encode($hashedCodes));

            return $plainCodes;
        }

        return false;
    }

    public function disable2fa(User $user): void
    {
        $this->userRepository->update2faSecret($user->id, null);
        $user->update(['google2fa_last_window' => null]);
    }

    public function verifyLogin(int $userId, string $otp): bool
    {
        $user = User::find($userId);

        if (! $user || ! $user->google2fa_secret) {
            return false;
        }

        if (strlen($otp) === 10 && $user->getAttribute('2fa_two_factor_recovery_codes')) {
            $savedCodes = json_decode($user->getAttribute('2fa_two_factor_recovery_codes'), true) ?? [];

            foreach ($savedCodes as $index => $hashedCode) {
                if (Hash::check($otp, $hashedCode)) {

                    unset($savedCodes[$index]);

                    $user->update([
                        '2fa_two_factor_recovery_codes' => json_encode(array_values($savedCodes)),
                    ]);

                    Auth::login($user);

                    return true;
                }
            }
        }

        $timestamp = $this->google2fa->verifyKeyNewer(
            $user->google2fa_secret,
            $otp,
            $user->google2fa_last_window,
        );

        if ($timestamp !== false) {
            $user->update(['google2fa_last_window' => $timestamp]);

            Auth::login($user);

            return true;
        }

        return false;
    }
}
