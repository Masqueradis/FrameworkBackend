<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $customer = Role::firstOrCreate(['name' => 'customer']);

        $editCatalog = Permission::firstOrCreate(['name' => 'edit-catalog']);
        $viewOrders = Permission::firstOrCreate(['name' => 'view-orders']);

        $admin->permissions()->syncWithoutDetaching([$editCatalog->id, $viewOrders->id]);
        $manager->permissions()->syncWithoutDetaching([$editCatalog->id]);
    }
}
