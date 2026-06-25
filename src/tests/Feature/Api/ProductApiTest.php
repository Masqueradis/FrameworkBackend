<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\DTO\Product\ProductSaveDTO;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $customerRole = Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $editProductsPermission = Permission::create(['name' => 'manage-all-products', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage-own-products', 'guard_name' => 'web']);
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
    public function test_can_get_paginated_products(): void
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
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'category' => ['id', 'name'],
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
        $this->assertCount(9, $response->json('data'));
    }

    #[Test]
    public function test_can_filter_products_by_price(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create(['price' => 100, 'category_id' => $category->id, 'available' => true]);
        Product::factory()->create(['price' => 5000, 'category_id' => $category->id, 'available' => true]);

        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/products?min_price=1000');
        $response->assertStatus(Response::HTTP_OK);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(5000, $response->json('data.0.price'));
    }

    #[Test]
    public function test_admin_can_create_product(): void
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
    public function test_customer_cannot_create_product(): void
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
    public function test_validation_fails_on_invalid_data(): void
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
    public function test_admin_can_update_product(): void
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
    public function test_admin_can_delete_product(): void
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
    public function test_can_get_specific_product(): void
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

    #[Test]
    public function test_product_response_contains_image_with_full_url(): void
    {
        $product = Product::factory()->create();

        $product->images()->create([
            'path' => 'product/test-image.jpg',
            'is_primary' => true,
            'position' => 0,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'images' => [
                        '*' => [
                            'id',
                            'url',
                            'is_primary',
                            'position',
                        ],
                    ],
                ],
            ]);

        $this->assertStringStartsWith('http', $response->json('data.images.0.url'));
    }

    #[Test]
    public function test_prepares_attributes_and_available_flag_in_dto(): void
    {
        $payload = [
            'category_id' => 1,
            'name' => 'RTX4090',
            'price' => 10000,
            'stock' => 50,
            'attribute_keys' => ['1', '', '3'],
            'attribute_values' => ['1', '2', '3'],
        ];

        $data = ProductSaveDTO::from($payload);

        $this->assertFalse($data->available);

        $this->assertEquals([
            '1' => '1',
            '3' => '3',
        ], $data->attributes);
    }

    #[Test]
    public function test_can_view_public_product_page(): void
    {
        $product = Product::factory()->create(['available' => true]);
        $response = $this->get(route('web.products.show', $product));
        $response->assertStatus(200);
        $response->assertViewIs('products.show');
    }
}
