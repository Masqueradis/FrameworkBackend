<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage all products',
            'manage own products',
            'manage categories',
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo(Permission::all());

        $sellerRole = Role::findOrCreate('seller', 'web');
        $sellerRole->givePermissionTo('manage own products');

        $customerRole = Role::findOrCreate('customer', 'web');

        $admin = User::firstOrCreate(
            [
            'email' => 'admin@example.com'
            ],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]);
        $admin->assignRole($adminRole);

        $seller = User::firstOrCreate([
            'email' => 'seller@example.com'
        ], [
            'name' => 'Sidorovich',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        $seller->assignRole($sellerRole);
    }
}
