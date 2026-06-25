<?php

declare(strict_types=1);

namespace Tests\Feature\Policy;

use App\Models\Permission;
use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_unsupported_actions_always_return_false(): void
    {
        Permission::firstOrCreate(['name' => 'manage-all-products']);
        Permission::firstOrCreate(['name' => 'manage-own-products']);

        $user = User::factory()->create();
        $product = Product::factory()->create();
        $policy = new ProductPolicy;

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $product));
        $this->assertFalse($policy->restore($user, $product));
        $this->assertFalse($policy->forceDelete($user, $product));
    }

    #[Test]
    public function test_admin_can_view_update_and_delete_any_product(): void
    {
        Permission::firstOrCreate(['name' => 'manage-all-products']);

        $admin = User::factory()->create();
        $admin->givePermissionTo('manage-all-products');

        $product = Product::factory()->create();
        $policy = new ProductPolicy;

        $this->assertTrue($policy->view($admin, $product));
        $this->assertTrue($policy->update($admin, $product));
        $this->assertTrue($policy->delete($admin, $product));
    }

    #[Test]
    public function test_seller_can_only_update_and_delete_own_product(): void
    {
        Permission::firstOrCreate(['name' => 'manage-all-products']);
        Permission::firstOrCreate(['name' => 'manage-own-products']);

        $seller = User::factory()->create();
        $seller->givePermissionTo('manage-own-products');

        $ownProduct = Product::factory()->create(['user_id' => $seller->id]);
        $otherProduct = Product::factory()->create();

        $policy = new ProductPolicy;

        $this->assertTrue($policy->update($seller, $ownProduct));
        $this->assertTrue($policy->delete($seller, $ownProduct));

        $this->assertFalse($policy->update($seller, $otherProduct));
        $this->assertFalse($policy->delete($seller, $otherProduct));
    }

    #[Test]
    public function test_customer_cannot_update_or_delete_product(): void
    {
        Permission::firstOrCreate(['name' => 'manage-all-products']);
        Permission::firstOrCreate(['name' => 'manage-own-products']);

        $customer = User::factory()->create();
        $product = Product::factory()->create();

        $policy = new ProductPolicy;

        $this->assertFalse($policy->update($customer, $product));
        $this->assertFalse($policy->delete($customer, $product));
    }
}
