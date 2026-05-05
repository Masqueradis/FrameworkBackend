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
