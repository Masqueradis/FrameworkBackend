<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Passport\Passport;
use Tests\TestCase;

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
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@email.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'access_token',
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

        $response->assertStatus(200)->assertJsonStructure([
            'user', 'access_token',
        ]);
    }

    #[Test]
    public function userCanLogout(): void
    {
        $user = User::factory()->create();

        Passport::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)->assertJson([
            'message' => 'Successfully logged out',
        ]);
    }

    #[Test]
    public function userWrongLogin(): void
    {
        $user = User::factory()->create([
            'email' => 'test@email.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)->assertJson([
            'message' => 'Unauthorized',
        ]);
    }
}
