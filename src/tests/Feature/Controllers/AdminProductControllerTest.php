<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Enums\CommentStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        Permission::firstOrCreate(['name' => 'manage-all-products']);
        Permission::firstOrCreate(['name' => 'manage-own-products']);

        $this->admin = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo('manage-all-products');
        $this->admin->assignRole('admin');

        $this->category = Category::factory()->create();

        $this->seller = User::factory()->create();
        $sellerRole = Role::firstOrCreate(['name' => 'seller']);
        $sellerRole->givePermissionTo('manage-own-products');
        $this->seller->assignRole('seller');
    }

    #[Test]
    public function test_displays_products_index_for_admin(): void
    {
        Product::factory()->count(10)->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.products.index', $this->category));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.products.index')
            ->assertViewHas('products');
    }

    #[Test]
    public function test_displays_products_index_for_seller(): void
    {
        Product::factory()->count(10)->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->seller)
            ->get(route('admin.products.index', $this->category));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.products.index')
            ->assertViewHas('products');
    }

    #[Test]
    public function test_displays_products_create_form(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.products.create', $this->category));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.products.form')
            ->assertViewHas('categories');
    }

    #[Test]
    public function test_stores_new_product(): void
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
    public function test_displays_product_edit_form(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.products.edit', $product));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.products.form')
            ->assertViewHasAll(['product', 'categories']);
    }

    #[Test]
    public function test_updates_product(): void
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
    public function test_deletes_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    #[Test]
    public function test_seller_deletes_product(): void
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
    public function test_shows_public_product_page_with_sorted_images(): void
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

    #[Test]
    public function test_authenticated_user_can_view_product_page_and_it_loads_their_comment(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => 'Test review',
            'rating' => 5,
            'status' => CommentStatus::Pending->value,
        ]);

        $response = $this->actingAs($user)->get(route('web.products.show', $product));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewHas('userComment');
    }

    #[Test]
    public function test_destroy_all_images_removes_files_from_storage_and_database(): void
    {
        Storage::fake('minio');

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $product = Product::factory()->create();

        $file1 = UploadedFile::fake()->image('photo1.jpg')->store('products', 'minio');
        $file2 = UploadedFile::fake()->image('photo2.jpg')->store('products', 'minio');

        ProductImage::factory()->create(['product_id' => $product->id, 'path' => $file1]);
        ProductImage::factory()->create(['product_id' => $product->id, 'path' => $file2]);

        Storage::disk('minio')->assertExists($file1);
        $this->assertDatabaseCount('product_images', 2);

        $response = $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->delete(route('admin.products.images.destroy-all', $product));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.products.edit', $product));
        $response->assertSessionHas('success', 'All images have been successfully removed.');

        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);
        Storage::disk('minio')->assertMissing($file1);
        Storage::disk('minio')->assertMissing($file2);
    }
}
