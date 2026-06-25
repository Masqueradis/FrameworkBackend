<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\ValueObjects\Id\ProductId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_finds_products_by_id(): void
    {
        $product = Product::factory()->create();
        $repository = new ProductRepository;

        $foundProduct = $repository->findById(new ProductId($product->id));

        $this->assertNotNull($foundProduct);
        $this->assertEquals($product->id, $foundProduct->id);
    }

    #[Test]
    public function test_returns_null_if_product_not_found(): void
    {
        $repository = new ProductRepository;

        $foundProduct = $repository->findById(new ProductId(1));

        $this->assertNull($foundProduct);
    }

    #[Test]
    public function test_chunk_all_products_processes_all_recoirds(): void
    {
        Product::factory()->count(10)->create();
        $repository = app(ProductRepository::class);

        $processedProducts = 0;
        $chunkCount = 0;

        $repository->chunkAllProducts(2, function ($chunk) use (&$processedProducts, &$chunkCount) {
            $chunkCount++;
            $processedProducts += $chunk->count();
        });

        $this->assertEquals(5, $chunkCount);
        $this->assertEquals(10, $processedProducts);
    }

    #[Test]
    public function test_count_all_returns_total_products(): void
    {
        Product::factory()->count(5)->create();

        $repository = app(ProductRepository::class);

        $count = $repository->countAll();

        $this->assertEquals(5, $count);
    }
}
