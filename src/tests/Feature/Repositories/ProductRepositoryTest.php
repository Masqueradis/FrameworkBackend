<?php

declare(strict_types=1);

namespace Feature\Repositories;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\ValueObjects\ProductId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itFindsProductsById(): void
    {
        $product = Product::factory()->create();
        $repository = new ProductRepository();

        $foundProduct = $repository->findById(new ProductId($product->id));

        $this->assertNotNull($foundProduct);
        $this->assertEquals($product->id, $foundProduct->id);
    }

    #[Test]
    public function itReturnsNullIfProductNotFound(): void
    {
        $repository = new ProductRepository();

        $foundProduct = $repository->findById(new ProductId(1));

        $this->assertNull($foundProduct);
    }
}
