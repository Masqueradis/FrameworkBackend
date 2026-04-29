<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UploadImageTest extends TestCase
{
    #[Test]
    public function testCanUploadImage(): void
    {
        Storage::fake('minio');

        $product = Product::factory()->create();
        $file = UploadedFile::fake()->image('image.jpg');

        $response = $this->postJson("/api/products/{$product->id}/images", [
            'image' => $file,
            'is_primary' => true,
            'position' => 1,
        ]);

        $response->assertStatus(201);

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
        $product = Product::factory()->create();

        $file = UploadedFile::fake()->create('image.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/products/{$product->id}/images", [
            'image' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertInvalid(['image']);
    }
}
