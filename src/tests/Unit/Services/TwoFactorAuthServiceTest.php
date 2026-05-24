<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TwoFactorAuthService;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TwoFactorAuthServiceTest extends TestCase
{
    private TwoFactorAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwoFactorAuthService();
    }

    public function testGeneratesValidSecretKey(): void
    {
        $secret = $this->service->generateSecret();

        $this->assertNotEmpty($secret);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGeneratesQrCodeUrl(): void
    {
        $url = $this->service->getQrCodeUrl(
            'MyApp',
            'test@example.com',
            'SUPERSECRET123'
        );

        $this->assertStringContainsString('MyApp', $url);
        $this->assertStringContainsString(urlencode('test@example.com'), $url);
        $this->assertStringContainsString('SUPERSECRET123', $url);
    }

    public function testVerifiesOtpCorrectly(): void
    {
        $secret = $this->service->generateSecret();

        $google2fa = app(Google2FA::class);
        $validOtp = $google2fa->getCurrentOtp($secret);

        $this->assertTrue($this->service->verify($secret, $validOtp));
        $this->assertFalse($this->service->verify($secret, '123456'));
    }
}
