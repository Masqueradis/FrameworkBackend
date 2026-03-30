<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Event;

class AuthTest extends TestCase
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
}
