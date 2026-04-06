<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
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

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@email.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_CREATED)->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'registered_at',
                ],
                'token',
            ],
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'test@email.com',
        ]);
    }

    #[Test]
    public function userCanLogin(): void
    {
        $user = User::factory()->create([
            'email' => 'test@email.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
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
        ])->postJson('/api/logout');

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

        $response = $this->postJson('/api/login', [
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

        $response->assertRedirect('/profile');
        $this->assertAuthenticated();
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
}
