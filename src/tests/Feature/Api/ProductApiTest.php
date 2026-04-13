<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private Category $category;

    public function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $customerRole = Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $editProductsPermission = Permission::create(['name' => 'manage all products', 'guard_name' => 'web']);
        $editCatalogPermission = Permission::create(['name' => 'edit-catalog', 'guard_name' => 'web']);

        $adminRole->givePermissionTo($editProductsPermission);
        $adminRole->givePermissionTo($editCatalogPermission);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->customer = User::factory()->create();
        $this->customer->assignRole($customerRole);

        $this->category = Category::factory()->create();


    }

    #[Test]
    public function testCanGetPaginatedProducts(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(20)->create([
            'category_id' => $category->id,
            'available' => true,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'price',
                            'category' => ['id', 'name'],
                        ],
                    ],
                    'meta' => ['current_page', 'last_page', 'total'],
                ],
            ]);
        $this->assertCount(9, $response->json('data.data'));
    }

    #[Test]
    public function testCanFilterProductsByPrice(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create(['price' => 100, 'category_id' => $category->id, 'available' => true]);
        Product::factory()->create(['price' => 5000, 'category_id' => $category->id, 'available' => true]);

        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/products?min_price=1000');
        $response->assertStatus(Response::HTTP_OK);

        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals(5000, $response->json('data.data.0.price'));
    }

    #[Test]
    public function testAdminCanCreateProduct(): void
    {
        $payload = [
            'name' => 'RTX4090',
            'description' => 'RTX4090',
            'price' => 10000,
            'category_id' => $this->category->id,
            'available' => true,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/products', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(['data']);

        $this->assertDatabaseHas('products', [
            'name' => 'RTX4090',
            'price' => 10000,
        ]);
    }

    #[Test]
    public function testCustomerCannotCreateProduct(): void
    {
        $payload = [
            'name' => 'RTX4090',
            'description' => 'RTX4090',
            'price' => 10000,
            'category_id' => $this->category->id,
            'available' => true,
        ];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/v1/products', $payload);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function testValidationFailsOnInvalidData(): void
    {
        $payload = [
            'name' => '',
            'description' => 'RTX4090',
            'price' => -10000,
            'category_id' => $this->category->id,
            'available' => true,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/products', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'price']);
    }

    #[Test]
    public function testAdminCanUpdateProduct(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 10000,
        ]);

        $payload = [
            'name' => 'RTX4090',
            'description' => $product->description,
            'price' => 9999,
            'category_id' => $this->category->id,
            'available' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/products/{$product->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'RTX4090',
            'price' => 9999,
        ]);
    }

    #[Test]
    public function testAdminCanDeleteProduct(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    #[Test]
    public function testCanGetSpecificProduct(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'RTX4090',
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name);
    }
}
