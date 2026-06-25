<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Http\Controllers\TwoFactorController;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:client', [
            '--personal' => true,
            '--name' => 'Test Client',
            '--provider' => 'users',
        ]);
    }

    #[Test]
    public function test_generate_puts_secret_and_qr_in_session(): void
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
    public function test_enable_redirects_back_with_errors_if_session_expired(): void
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
    public function test_enable_activates2_fa_with_valid_otp(): void
    {
        $user = User::factory()->create();
        $google2fa = new Google2FA;
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
    public function test_enable_fails_with_invalid_otp(): void
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
    public function test_disable_removes2fa(): void
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
    public function test_show_verify_form_redirects_to_login_if_no_session(): void
    {
        $response = $this->get(action([TwoFactorController::class, 'showVerifyForm']));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function test_show_verify_form_displays_view_if_session_exists(): void
    {
        $response = $this->withSession(['2fa:user_id' => 1])
            ->get(action([TwoFactorController::class, 'showVerifyForm']));

        $response->assertOk();
        $response->assertViewIs('auth.2fa');
    }

    #[Test]
    public function test_verify_login_redirects_to_login_if_no_session(): void
    {
        $response = $this->post(action([TwoFactorController::class, 'verifyLogin']), [
            'otp' => '123456',
        ]);

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function test_verify_login_authenticates_with_valid_otp(): void
    {
        $google2fa = new Google2FA;
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
    public function test_verify_login_fails_with_invalid_otp(): void
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
    #[Test]
    public function test_verify_login_api_fails_without_user_id(): void
    {
        $response = $this->postJson(action([TwoFactorController::class, 'verifyLogin']), [
            'otp' => '123456'
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson([
            'success' => false,
            'message' => 'User ID is required.'
        ]);
    }

    #[Test]
    public function test_verify_login_api_succeeds_with_valid_otp(): void
    {
        $user = User::factory()->create();

        $this->mock(TwoFactorAuthService::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('verifyLogin')
                ->once()
                ->with($user->id, '123456')
                ->andReturn(true);
        });

        $response = $this->postJson(action([TwoFactorController::class, 'verifyLogin']), [
            'user_id' => $user->id,
            'otp' => '123456'
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token'
            ]
        ]);
    }

    #[Test]
    public function test_verify_login_api_fails_with_invalid_otp(): void
    {
        $user = User::factory()->create();

        $this->mock(TwoFactorAuthService::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('verifyLogin')
                ->once()
                ->with($user->id, 'wrong_otp')
                ->andReturn(false);
        });

        $response = $this->postJson(action([TwoFactorController::class, 'verifyLogin']), [
            'user_id' => $user->id,
            'otp' => 'wrong_otp'
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid verification code.'
        ]);
    }
}
