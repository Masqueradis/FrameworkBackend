<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UploadImageTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function testCanUploadImage(): void
    {
        Storage::fake('minio');

        $user = User::factory()->create();

        Role::firstOrCreate(['name' => 'seller']);
        $user->assignRole('seller');
        $this->actingAs($user);

        $product = Product::factory()->create([
            'user_id' => $user->id,
        ]);

        $file = UploadedFile::fake()->image('image.jpg');

        $response = $this->postJson("/admin/products/{$product->id}/images", [
            'image' => $file,
            'is_primary' => true,
            'position' => 1,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        Storage::disk('minio')->assertExists('products/' . $file->hashName());

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'is_primary' => true,
        ]);
    }

    #[Test]
    public function testCannotUploadPDFAsImage(): void
    {
        Storage::fake('minio');

        $user = User::factory()->create();

        Role::firstOrCreate(['name' => 'seller']);
        $user->assignRole('seller');
        $this->actingAs($user);

        $product = Product::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->create('image.pdf', 100, 'application/pdf');

        $response = $this->postJson("/admin/products/{$product->id}/images", [
            'image' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertInvalid(['image']);
    }

    #[Test]
    public function testProductImageBelongsToProduct(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(Product::class, $image->product);
        $this->assertEquals($product->id, $image->product->id);
    }
}
