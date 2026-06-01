<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TwoFactorAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactorAuthService $service;
    private UserRepository $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TwoFactorAuthService::class);
    }

    #[Test]
    public function testGeneratesValidSecretKey(): void
    {
        $secret = $this->service->generateSecret();

        $this->assertNotEmpty($secret);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    #[Test]
    public function testGeneratesQrCodeUrl(): void
    {
        $url = $this->service->getQrCodeUrl(
            'MyApp',
            'test@example.com',
            'SUPERSECRET12345'
        );

        $this->assertStringContainsString('MyApp', $url);
        $this->assertStringContainsString(urlencode('test@example.com'), $url);
        $this->assertStringContainsString('SUPERSECRET12345', $url);
    }

    #[Test]
    public function testVerifiesOtpCorrectly(): void
    {
        $secret = $this->service->generateSecret();

        $google2fa = app(Google2FA::class);
        $validOtp = $google2fa->getCurrentOtp($secret);

        $this->assertTrue($this->service->verify($secret, $validOtp));
        $this->assertFalse($this->service->verify($secret, '123456'));
    }

    #[Test]
    public function testEnable2faReturnsTrueAndUpdatesSecretOnValidOtp(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->generateSecret();

        $otp = new Google2FA()->getCurrentOtp($secret);

        $result = $this->service->enable2fa($user, $secret, $otp);

        $this->assertTrue($result);
        $this->assertEquals($secret, $user->fresh()->google2fa_secret);
    }

    #[Test]
    public function testEnable2faReturnsFalseOnInvalidOtp(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->generateSecret();

        $result = $this->service->enable2fa($user, $secret, '000000');

        $this->assertFalse($result);
        $this->assertNull($user->fresh()->google2fa_secret);
    }

    #[Test]
    public function testDisable2faClearsSecret(): void
    {
        $secret = $this->service->generateSecret();
        $user = User::factory()->create(['google2fa_secret' => $secret]);

        $this->service->disable2fa($user);

        $this->assertNull($user->fresh()->google2fa_secret);
    }

    #[Test]
    public function testVerifyLoginReturnsFalseIfUserNotFoundOrNoSecret(): void
    {
        $this->assertFalse($this->service->verifyLogin(99999, '123456'));

        $user = User::factory()->create(['google2fa_secret' => null]);
        $this->assertFalse($this->service->verifyLogin($user->id, '123456'));
    }

    #[Test]
    public function testVerifyLoginReturnsTrueAndAuthenticatesUser(): void
    {
        $secret = $this->service->generateSecret();
        $user = User::factory()->create(['google2fa_secret' => $secret]);

        $otp = new Google2FA()->getCurrentOtp($secret);

        $result = $this->service->verifyLogin($user->id, $otp);

        $this->assertTrue($result);
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function testVerifyLoginReturnsFalseOnInvalidOtp(): void
    {
        $secret = $this->service->generateSecret();
        $user = User::factory()->create(['google2fa_secret' => $secret]);

        $result = $this->service->verifyLogin($user->id, '000000');

        $this->assertFalse($result);
        $this->assertGuest();
    }
}
