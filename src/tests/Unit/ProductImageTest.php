<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_product_image_attributes_and_relationships(): void
    {
        $product = Product::factory()->create();

        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/example.jpg',
            'is_primary' => true,
            'position' => 1,
        ]);

        $this->assertEquals('products/example.jpg', $image->path);
        $this->assertTrue($image->is_primary);
        $this->assertEquals(1, $image->position);
        $this->assertCount(1, $product->images);
        $this->assertInstanceOf(ProductImage::class, $product->images->first());
        $this->assertEquals($image->id, $product->images->first()->id);
    }
}
