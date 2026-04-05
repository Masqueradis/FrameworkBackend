<?php

declare(strict_types=1);

namespace Feature\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testRoleBelongsToManyUsers(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();

        $role->users()->attach($user);

        $this->assertTrue($role->users->contains('id', $user->id));
    }

    #[Test]
    public function testPermissionBelongsToManyRoles(): void
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();

        $permission->roles()->attach($role);

        $this->assertTrue($permission->roles->contains('id', $role->id));
    }
}
