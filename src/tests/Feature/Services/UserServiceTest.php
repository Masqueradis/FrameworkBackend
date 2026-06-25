<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

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
    public function test_can_assign_new_role_to_user(): void
    {
        Role::firstOrCreate(['name' => UserRole::Admin->value, 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);

        $user = User::factory()->create();
        $user->assignRole(UserRole::Customer->value);

        $this->userService->assignRole($admin, $user, UserRole::Seller);

        $this->assertTrue($user->fresh()->isSeller());
        $this->assertFalse($user->fresh()->hasRole(UserRole::Customer->value));
    }

    #[Test]
    public function test_can_ban_user(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active->value]);

        $this->userService->banUser($user);

        $this->assertTrue($user->fresh()->isBanned());
    }

    #[Test]
    public function test_assign_role_throws_exception_for_unauthorized_performer(): void
    {
        $seller = User::factory()->create();
        $seller->assignRole(UserRole::Seller->value);

        $target = User::factory()->create();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied.');

        $this->userService->assignRole($seller, $target, UserRole::Admin);
    }

    #[Test]
    public function test_can_unban_user(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Banned->value]);

        $this->userService->unbanUser($user);

        $this->assertFalse($user->fresh()->isBanned());
    }

    #[Test]
    public function test_can_get_paginated_users(): void
    {
        User::factory()->count(10)->create();

        $result = $this->userService->getPaginatedUsers(5);

        $this->assertEquals(5, $result->count());
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }
}
