<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        Role::firstOrCreate(['name' => 'admin']);
        $this->admin->assignRole('admin');
        $this->category = Category::factory()->create();

        $this->seller = User::factory()->create();
        Role::firstOrCreate(['name' => 'seller']);
        $this->seller->assignRole('seller');
    }

    #[Test]
    public function testDisplaysProductsIndexForAdmin(): void
    {
        Product::factory()->count(10)->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.products.index', $this->category));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.products.index')
            ->assertViewHas('products');
    }

    #[Test]
    public function testDisplaysProductsIndexForSeller(): void
    {
        Product::factory()->count(10)->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->seller)
            ->get(route('admin.products.index', $this->category));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.products.index')
            ->assertViewHas('products');
    }

    #[Test]
    public function testDisplaysProductsCreateForm(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.products.create', $this->category));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.products.form')
            ->assertViewHas('categories');
    }

    #[Test]
    public function testStoresNewProduct(): void
    {
        $payload = [
            'name' => 'New Product',
            'category_id' => $this->category->id,
            'price' => 67,
            'stock' => 52,
            'available' => true,
            'description' => 'New product',
            'attributes' => [],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $payload);

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'price' => 67,
            'stock' => 52,
            'available' => true,
        ]);
    }

    #[Test]
    public function testDisplaysProductEditForm(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.products.edit', $product));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.products.form')
            ->assertViewHasAll(['product', 'categories']);
    }

    #[Test]
    public function testUpdatesProduct(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Old Product']);

        $payload = [
            'name' => 'New Product',
            'category_id' => $this->category->id,
            'price' => 67,
            'stock' => 52,
            'available' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $payload);

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Product',
            'price' => 67,
            'stock' => 52,
            'available' => true,
        ]);
    }

    #[Test]
    public function testDeletesProduct(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    #[Test]
    public function testSellerDeletesProduct(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'user_id' => $this->seller->id,
        ]);

        $response = $this->actingAs($this->seller)
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    #[Test]
    public function testShowsPublicProductPageWithSortedImages(): void
    {
        $product = Product::factory()->create(['available' => true]);

        ProductImage::insert([
            ['product_id' => $product->id, 'path' => 'product-1.jpg', 'is_primary' => false, 'position' => 2],
            ['product_id' => $product->id, 'path' => 'product-2.jpg', 'is_primary' => true, 'position' => 1],
        ]);

        $response = $this->get(route('web.products.show', $product));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('products.show');
        $response->assertViewHas('product');
        $response->assertViewHas('images');
    }
}
