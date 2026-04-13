<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    #[Test]
    public function testRoleBelongsToManyUsers(): void
    {
        $role = Role::factory()->create(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
    }
}
