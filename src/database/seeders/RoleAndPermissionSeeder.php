<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage-all-products',
            'manage-own-products',
            'manage-categories',
            'manage-users',
            'assign-seller-role',
            'assign-admin-role',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo(Permission::all());

        Role::findOrCreate('manager', 'web')->givePermissionTo([
            'manage-all-products',
            'manage-categories',
            'manage-users',
            'assign-seller-role',
        ]);

        Role::findOrCreate('seller', 'web')->givePermissionTo('manage-own-products');
        Role::findOrCreate('customer', 'web');

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );
        $admin->assignRole($adminRole);
    }
}
