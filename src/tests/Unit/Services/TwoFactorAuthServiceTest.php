<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

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
    public function test_generates_valid_secret_key(): void
    {
        $secret = $this->service->generateSecret();

        $this->assertNotEmpty($secret);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    #[Test]
    public function test_generates_qr_code_url(): void
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
    public function test_verifies_otp_correctly(): void
    {
        $secret = $this->service->generateSecret();

        $google2fa = app(Google2FA::class);
        $validOtp = $google2fa->getCurrentOtp($secret);

        $this->assertTrue($this->service->verify($secret, $validOtp));
        $this->assertFalse($this->service->verify($secret, '123456'));
    }

    #[Test]
    public function test_enable2fa_returns_true_and_updates_secret_on_valid_otp(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->generateSecret();

        $otp = new Google2FA()->getCurrentOtp($secret);

        $result = $this->service->enable2fa($user, $secret, $otp);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function test_enable2fa_returns_false_on_invalid_otp(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->generateSecret();

        $result = $this->service->enable2fa($user, $secret, '000000');

        $this->assertFalse($result);
        $this->assertNull($user->fresh()->google2fa_secret);
    }

    #[Test]
    public function test_disable2fa_clears_secret(): void
    {
        $secret = $this->service->generateSecret();
        $user = User::factory()->create(['google2fa_secret' => $secret]);

        $this->service->disable2fa($user);

        $this->assertNull($user->fresh()->google2fa_secret);
    }

    #[Test]
    public function test_verify_login_returns_false_if_user_not_found_or_no_secret(): void
    {
        $this->assertFalse($this->service->verifyLogin(99999, '123456'));

        $user = User::factory()->create(['google2fa_secret' => null]);
        $this->assertFalse($this->service->verifyLogin($user->id, '123456'));
    }

    #[Test]
    public function test_verify_login_returns_true_and_authenticates_user(): void
    {
        $secret = $this->service->generateSecret();
        $user = User::factory()->create(['google2fa_secret' => $secret]);

        $otp = new Google2FA()->getCurrentOtp($secret);

        $result = $this->service->verifyLogin($user->id, $otp);

        $this->assertTrue($result);
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function test_verify_login_returns_false_on_invalid_otp(): void
    {
        $secret = $this->service->generateSecret();
        $user = User::factory()->create(['google2fa_secret' => $secret]);

        $result = $this->service->verifyLogin($user->id, '000000');

        $this->assertFalse($result);
        $this->assertGuest();
    }

    #[Test]
    public function test_verify_login_authenticates_with_recovery_code_and_burns_it(): void
    {
        $user = User::factory()->create();
        $recoveryCode = '1234567890';
        $hashedCode = Hash::make($recoveryCode);

        $user->update([
            'google2fa_secret' => 'DUMMYSECRETKEY',
            '2fa_two_factor_recovery_codes' => json_encode([$hashedCode])
        ]);

        $result = $this->service->verifyLogin($user->id, $recoveryCode);

        $this->assertTrue($result);
        $this->assertAuthenticatedAs($user);

        $savedCodes = json_decode($user->fresh()->getAttribute('2fa_two_factor_recovery_codes'), true);
        $this->assertEmpty($savedCodes);
    }
}
