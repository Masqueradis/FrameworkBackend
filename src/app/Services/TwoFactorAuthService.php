<?php

declare(strict_types=1);

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    private Google2FA $google2fa;

    public function __construct()
    {
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

    public function verify(string $secret, string $otp): bool
    {
        return $this->google2fa->verifyKey($secret, $otp);
    }
}
