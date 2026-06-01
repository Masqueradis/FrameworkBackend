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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
    public function testCanBanUser(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active->value]);

        $this->userService->banUser($user);

        $this->assertTrue($user->fresh()->isBanned());
    }

    #[Test]
    public function testAssignRoleThrowsExceptionForUnauthorizedPerformer(): void
    {
        $seller = User::factory()->create();
        $seller->assignRole(UserRole::Seller->value);

        $target = User::factory()->create();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied.');

        $this->userService->assignRole($seller, $target, UserRole::Admin);
    }

    #[Test]
    public function testCanUnbanUser(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Banned->value]);

        $this->userService->unbanUser($user);

        $this->assertFalse($user->fresh()->isBanned());
    }

    #[Test]
    public function testCanGetPaginatedUsers(): void
    {
        User::factory()->count(10)->create();

        $result = $this->userService->getPaginatedUsers(5);

        $this->assertEquals(5, $result->count());
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }
}
