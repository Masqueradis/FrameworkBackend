<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => UserRole::Customer->value, 'guard_name' => 'web']);
        Role::create(['name' => UserRole::Seller->value, 'guard_name' => 'web']);

        $this->userService = app(UserService::class);
    }

    #[Test]
    public function testCanAssignNewRoleToUser(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Customer->value);

        $this->userService->assignRole($user, UserRole::Seller);

        $this->assertTrue($user->fresh()->isSeller());
        $this->assertFalse($user->fresh()->hasRole(UserRole::Customer->value));
    }

    #[Test]
    public function testCanBanUser(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active->value]);

        $this->userService->banUser($user);

        $this->assertTrue($user->fresh()->isBanned());
    }
}
