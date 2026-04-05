<?php

declare(strict_types=1);

namespace Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    #[Test]
    public function testDisplaysProductsIndex(): void
    {
        Product::factory()->count(10)->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)
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
}
