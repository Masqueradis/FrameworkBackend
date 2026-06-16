<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Http\Controllers\TwoFactorController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testGeneratePutsSecretAndQrInSession(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/profile')
            ->post(action([TwoFactorController::class, 'generate']));

        $response->assertRedirect('/profile');
        $response->assertSessionHas('2fa_secret');
        $response->assertSessionHas('2fa_qr');
    }

    #[Test]
    public function testEnableRedirectsBackWithErrorsIfSessionExpired(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/profile')
            ->post(action([TwoFactorController::class, 'enable']), [
                'otp' => '123456',
            ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrors(['otp' => 'Session expired. Please generate a new QR code.']);
    }

    #[Test]
    public function testEnableActivates2FaWithValidOtp(): void
    {
        $user = User::factory()->create();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $otp = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($user)
            ->withSession(['2fa_secret' => $secret, '2fa_qr' => 'qr_code_url'])
            ->from('/profile')
            ->post(action([TwoFactorController::class, 'enable']), [
                'otp' => $otp,
            ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success', 'Two factor authentication successfully enabled.');
        $response->assertSessionMissing('2fa_secret');

        $this->assertNotNull($user->fresh()->google2fa_secret);
    }

    #[Test]
    public function testEnableFailsWithInvalidOtp(): void
    {
        $user = User::factory()->create();
        $secret = new Google2FA()->generateSecretKey();

        $response = $this->actingAs($user)
            ->withSession(['2fa_secret' => $secret])
            ->from('/profile')
            ->post(action([TwoFactorController::class, 'enable']), [
                'otp' => '000000',
            ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrors(['otp' => 'Invalid verification code. Please try again.']);
        $this->assertNull($user->fresh()->google2fa_secret);
    }

    #[Test]
    public function testDisableRemoves2fa(): void
    {
        $user = User::factory()->create(['google2fa_secret' => 'SECRET1234567890']);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->delete(action([TwoFactorController::class, 'disable']));

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success', 'Two factor authentication successfully disabled.');
        $this->assertNull($user->fresh()->google2fa_secret);
    }

    #[Test]
    public function testShowVerifyFormRedirectsToLoginIfNoSession(): void
    {
        $response = $this->get(action([TwoFactorController::class, 'showVerifyForm']));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function testShowVerifyFormDisplaysViewIfSessionExists(): void
    {
        $response = $this->withSession(['2fa:user_id' => 1])
            ->get(action([TwoFactorController::class, 'showVerifyForm']));

        $response->assertOk();
        $response->assertViewIs('auth.2fa');
    }

    #[Test]
    public function testVerifyLoginRedirectsToLoginIfNoSession(): void
    {
        $response = $this->post(action([TwoFactorController::class, 'verifyLogin']), [
            'otp' => '123456',
        ]);

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function testVerifyLoginAuthenticatesWithValidOtp(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->create(['google2fa_secret' => $secret]);
        $otp = $google2fa->getCurrentOtp($secret);

        $response = $this->withSession(['2fa:user_id' => $user->id])
            ->post(action([TwoFactorController::class, 'verifyLogin']), [
                'otp' => $otp,
            ]);

        $response->assertRedirect('/profile');
        $response->assertSessionMissing('2fa:user_id');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function testVerifyLoginFailsWithInvalidOtp(): void
    {
        $secret = new Google2FA()->generateSecretKey();
        $user = User::factory()->create(['google2fa_secret' => $secret]);

        $response = $this->from('/2fa')
            ->withSession(['2fa:user_id' => $user->id])
            ->post(action([TwoFactorController::class, 'verifyLogin']), [
                'otp' => '000000',
            ]);

        $response->assertRedirect('/2fa');
        $response->assertSessionHasErrors(['otp' => 'Invalid verification code. Please try again.']);
        $this->assertGuest();
    }
}
