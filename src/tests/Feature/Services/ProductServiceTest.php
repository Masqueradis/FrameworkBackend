<?php

declare(strict_types=1);

namespace Feature\Services;

use App\Data\ProductIndexData;
use App\Data\ProductSaveData;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;

    public function setUp(): void
    {
        parent::setUp();
        $this->productService = new ProductService();
    }

    public function testFilterProductsByMaxPrice(): void
    {
        Product::factory()->create(['price' => 100, 'available' => true]);
        Product::factory()->create(['price' => 200, 'available' => true]);

        $data = ProductIndexData::from(['max_price' => 200]);

        $result = $this->productService->getFilteredProducts($data);

        $this->assertEquals(2, $result->total());
        $this->assertEquals(100, $result->first()->price);
    }

    #[Test]
    public function testFiltersProductsBySearch(): void
    {
        Product::factory()->create(['name' => 'First', 'description' => 'First description', 'available' => true]);
        Product::factory()->create(['name' => 'Second', 'description' => 'Second description', 'available' => true]);
        Product::factory()->create(['name' => 'Third', 'description' => 'Third', 'available' => true]);

        $data = ProductIndexData::from(['search' => 'description']);

        $result = $this->productService->getFilteredProducts($data);

        $this->assertEquals(2, $result->total());
    }

    #[Test]
    public function testFiltersProductsByAttributes(): void
    {
        Product::factory()->create(['available' => true,
            'attributes' => [
                'RAM' => 'DDR4',
                'volume' => '32 GB',
            ]]);
        Product::factory()->create(['available' => true,
            'attributes' => [
                'RAM' => 'DDR5',
                'volume' => '64 GB',
            ]]);

        $data = ProductIndexData::from(['attributes' => [
            'RAM' => ['DDR4', 'DDR5'],
            'volume' => '32 GB',
        ]]);

        $result = $this->productService->getFilteredProducts($data);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('DDR4', $result->first()->attributes['RAM']);
    }

    #[Test]
    public function testUpdateProductWithExplicitSKU(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['sku' => 'OLD-SKU']);

        $data = new ProductSaveData(
            categoryId: $category->id,
            name: 'Updated Name',
            price: 200,
            stock: 10,
            available: true,
            description: 'Updated Description',
            sku: 'NEW-CUSTOM-SKU',
            attributes: []
        );

        $updateProduct = $this->productService->updateProduct($product, $data);

        $this->assertEquals('NEW-CUSTOM-SKU', $updateProduct->sku);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => 'NEW-CUSTOM-SKU',
        ]);
    }

    #[Test]
    public function testReturnsPaginatedProductsForAdminWithRelations(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $paginator = $this->productService->getPaginatedProductsForAdmin(2);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(3, $paginator->total());
        $this->assertCount(2, $paginator->items());
        $this->assertTrue($paginator->first()->relationLoaded('category'));
    }
}
