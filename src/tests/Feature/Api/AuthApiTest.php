<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Data\AuthResultData;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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
    public function userCanRegister(): void
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
    public function userCanLogin(): void
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
    public function userCanLogout(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('Test Token')->accessToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
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
    public function wrongRespond(): void
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
    public function authServiceCanRevokeToken(): void
    {
        /** @var \Mockery\MockInterface&\Laravel\Passport\Token $tokenMock */
        $tokenMock = \Mockery::mock(\Laravel\Passport\Token::class . ', \Laravel\Passport\Contracts\ScopeAuthorizable');
        $tokenMock->expects('revoke');
        /** @var \Mockery\MockInterface&\App\Models\User $userMock */
        $userMock = \Mockery::mock(\App\Models\User::class)->makePartial();
        $userMock->shouldReceive('token')->andReturn($tokenMock);
        $authService = app(\App\Services\AuthService::class);
        $authService->logout($userMock);
    }

    #[Test]
    public function testShowsLoginForm(): void
    {
        $response = $this->get('login');

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('auth.login');
    }

    #[Test]
    public function testShowsRegisterForm(): void
    {
        $response = $this->get('register');

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('auth.register');
    }

    #[Test]
    public function testRegisterWeb(): void
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
    public function testLoginWeb(): void
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
    public function testLogoutWeb(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    #[Test]
    public function testRegisterRemovesOldTokenIfExists(): void
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

    public function testVerifyRegistrationSuccessfully(): void
    {
        $service = app(AuthService::class);
        $token = 'valid-test-token';
        $email = 'test@email.com';

        Cache::put('pending_email_' . $token, [
            'name' => 'Verify User',
            'email' => $email,
            'password' => 'password',
        ], now()->addMinutes(30));

        Role::findOrCreate('customer', 'web');

        $result = $service->verifyRegistration($token);

        $this->assertInstanceOf(AuthResultData::class, $result);
        $this->assertDatabaseHas('users', ['email' => $email]);
        $this->assertTrue($result->user->hasRole('customer', 'web'));
        $this->assertNull(Cache::get('pending_email_' . $token));
    }

    #[Test]
    public function testVerifyRegistrationFailsWithInvalidToken(): void
    {
        $service = app(AuthService::class);

        $this->expectException(ValidationException::class);

        $service->verifyRegistration('invalid-token');
    }

    #[Test]
    public function testProfileIndexReturnsDashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('dashboard');
    }

    #[Test]
    public function testVerifyEmailApiFailsWithInvalidToken(): void
    {
        $response = $this->getJson('/api/v1/verify/invalid-token');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('message', 'Link invalid or expired.');
    }

    #[Test]
    public function testVerifyEmailWebFailsWithInvalidToken(): void
    {
        $response = $this->get('/verify/invalid-token');

        $response->assertRedirect('register')
            ->assertSessionHasErrors(['email' => 'The link is expired or invalid. Please register again.']);
    }

    #[Test]
    public function testVerifyEmailWebSuccessRedirectsToProfile(): void
    {
        $token = 'web-valid-token';
        Cache::put('pending_email_' . $token, [
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
    public function testGetApiUserReturnsUserData(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->get('/api/v1/user');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('id', $user->id);
    }

    #[Test]
    public function testLogoutWithoutUserAborts(): void
    {
        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function testShowsVerificationNoticeView(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->get('email/verify');

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('auth.verify-email');
    }

    #[Test]
    public function testResendsVerificationEmail(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('email/verification-notification');

        $response->assertRedirect()
            ->assertSessionHas('message', 'Verification link sent!');
    }

    #[Test]
    public function testVerifyEmailApiSuccessReturnsJsonAndToken(): void
    {
        $token = 'valid-token';
        $email = 'test@email.com';

        Cache::put('pending_email_' . $token, [
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
                    'token'
                ]
            ]);
        $this->assertNull(Cache::get('pending_email_' . $token));
    }
}
