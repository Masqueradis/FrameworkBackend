<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'manage-users']);
        Role::firstOrCreate(['name' => UserRole::Admin->value, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => UserRole::Seller->value, 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(UserRole::Admin->value);
        $this->admin->givePermissionTo('manage-users');
    }

    #[Test]
    public function test_index_displays_users_list(): void
    {
        User::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertViewIs('admin.users.index');
        $response->assertViewHas('users');
    }

    #[Test]
    public function test_ban_user_redirects_with_success_message(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.ban', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('message', "User {$user->name} has been banned.");
        $this->assertTrue($user->fresh()->isBanned());
    }

    #[Test]
    public function test_unban_user_redirects_with_success_message(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Banned]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.unban', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('message', "User {$user->name} has been unbanned.");
        $this->assertFalse($user->fresh()->isBanned());
    }

    #[Test]
    public function test_assign_role_updates_user_role_and_redirects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.assign-role', $user), [
                'role' => UserRole::Seller->value,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('message', 'User role successfully changed.');
        $this->assertTrue($user->fresh()->isSeller());
    }
}
