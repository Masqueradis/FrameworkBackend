<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\DTO\User\AuthResultDTO;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Token;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:keys');

        $this->artisan('passport:client', [
            '--personal' => true,
            '--name' => 'Test User',
            '--provider' => 'users',
        ]);
    }

    #[Test]
    public function user_can_register(): void
    {
        Event::fake();

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@email.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_ACCEPTED)->assertJsonStructure([
            'success',
            'message',
            'data' => [],
        ]);

    }

    #[Test]
    public function user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@email.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_OK)->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user',
                'token',
            ],
        ]);
    }

    #[Test]
    public function user_can_logout(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('Test Token')->accessToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/logout');

        $response->assertStatus(Response::HTTP_OK)->assertJson([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'user_id' => $user->id,
            'revoked' => false,
        ]);
    }

    #[Test]
    public function wrong_respond(): void
    {
        $user = User::factory()->create([
            'email' => 'test@email.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function auth_service_can_revoke_token(): void
    {
        /** @var MockInterface&Token $tokenMock */
        $tokenMock = \Mockery::mock(Token::class.', \Laravel\Passport\Contracts\ScopeAuthorizable');
        $tokenMock->expects('revoke');
        /** @var MockInterface&User $userMock */
        $userMock = \Mockery::mock(User::class)->makePartial();
        $userMock->shouldReceive('token')->andReturn($tokenMock);
        $authService = app(AuthService::class);
        $authService->logout($userMock);
    }

    #[Test]
    public function test_shows_login_form(): void
    {
        $response = $this->get('login');

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('auth.login');
    }

    #[Test]
    public function test_shows_register_form(): void
    {
        $response = $this->get('register');

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('auth.register');
    }

    #[Test]
    public function test_register_web(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@email.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/login');

        $this->assertGuest();
    }

    #[Test]
    public function test_login_web(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/profile');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function test_logout_web(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    #[Test]
    public function test_register_removes_old_token_if_exists(): void
    {
        Cache::put('pending_email_test@email.com', 'old-token', now()->addMinutes(30));

        $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@email.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertNull(Cache::get('pending_email_old-token'));
    }

    public function test_verify_registration_successfully(): void
    {
        $service = app(AuthService::class);
        $token = 'valid-test-token';
        $email = 'test@email.com';

        Cache::put('pending_email_'.$token, [
            'name' => 'Verify User',
            'email' => $email,
            'password' => 'password',
        ], now()->addMinutes(30));

        Role::findOrCreate('customer', 'web');

        $result = $service->verifyRegistration($token);

        $this->assertInstanceOf(AuthResultDTO::class, $result);
        $this->assertDatabaseHas('users', ['email' => $email]);
        $this->assertTrue($result->user->hasRole('customer', 'web'));
        $this->assertNull(Cache::get('pending_email_'.$token));
    }

    #[Test]
    public function test_verify_registration_fails_with_invalid_token(): void
    {
        $service = app(AuthService::class);

        $this->expectException(ValidationException::class);

        $service->verifyRegistration('invalid-token');
    }

    #[Test]
    public function test_profile_index_returns_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('profile.dashboard');
    }

    #[Test]
    public function test_verify_email_api_fails_with_invalid_token(): void
    {
        $response = $this->getJson('/api/v1/verify/invalid-token');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('message', 'Link invalid or expired.');
    }

    #[Test]
    public function test_verify_email_web_fails_with_invalid_token(): void
    {
        $response = $this->get('/verify/invalid-token');

        $response->assertRedirect('register')
            ->assertSessionHasErrors(['email' => 'The link is expired or invalid. Please register again.']);
    }

    #[Test]
    public function test_verify_email_web_success_redirects_to_profile(): void
    {
        $token = 'web-valid-token';
        Cache::put('pending_email_'.$token, [
            'name' => 'Web user',
            'email' => 'test@email.com',
            'password' => 'password',
        ], now()->addMinutes(30));

        Role::findOrCreate('customer', 'web');

        $response = $this->get("/verify/{$token}");

        $response->assertRedirect('/profile')
            ->assertSessionHas('status', 'Email verified. Welcome');
    }

    #[Test]
    public function test_get_api_user_returns_user_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->get('/api/v1/user');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.id', $user->id);
    }

    #[Test]
    public function test_logout_without_user_aborts(): void
    {
        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function test_shows_verification_notice_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->get('email/verify');

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('auth.verify-email');
    }

    #[Test]
    public function test_resends_verification_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('email/verification-notification');

        $response->assertRedirect()
            ->assertSessionHas('message', 'Verification link sent!');
    }

    #[Test]
    public function test_verify_email_api_success_returns_json_and_token(): void
    {
        $token = 'valid-token';
        $email = 'test@email.com';

        Cache::put('pending_email_'.$token, [
            'name' => 'Verify User',
            'email' => $email,
            'password' => 'password',
        ], now()->addMinutes(30));

        Role::findOrCreate('customer', 'web');

        $response = $this->getJson("/api/v1/verify/{$token}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => 'Email verified and registered successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);
        $this->assertNull(Cache::get('pending_email_'.$token));
    }

    #[Test]
    public function test_login_api_requires2fa_if_secret_is_set(): void
    {
        $password = 'Password123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'google2fa_secret' => 'SECRETKEY1234567',
        ]);

        $response = $this->postJson(route('login.post'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.require_2fa', true);
        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('message', 'Two factor authentication was successful.');
    }

    #[Test]
    public function test_login_web_requires2fa_if_secret_is_set(): void
    {
        $password = 'Password123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'google2fa_secret' => 'SECRETKEY1234567',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('login.2fa'));
        $response->assertSessionHas('2fa:user_id', $user->id);
    }
}
