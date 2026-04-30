<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Data\ProductIndexData;
use App\Data\ProductSaveData;
use App\Data\UploadImageData;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use App\ValueObjects\CategoryId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;

    public function setUp(): void
    {
        parent::setUp();
        $this->productService = app(ProductService::class);
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
            categoryId: new CategoryId($category->id),
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

    #[Test]
    public function testHandlesImageUploadsAndRespectsMaxLimits(): void
    {
        Storage::fake('minio');

        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(ProductService::class);
        $category = Category::factory()->create();

        $initialImages = [];
        for ($i = 0; $i < 8; $i++) {
            $initialImages[] = UploadedFile::fake()->image("img_{$i}.jpg");
        }

        $createData = ProductSaveData::from([
            'category_id' => new CategoryId($category->id),
            'name' => 'New Name',
            'price' => 100,
            'images' => $initialImages,
        ]);

        $product = $service->createProduct($createData);

        $product->refresh();

        $this->assertCount(8, $product->images);
        $this->assertTrue($product->images->first()->is_primary);

        $newImages = [];
        for ($i = 0; $i < 5; $i++) {
            $newImages[] = UploadedFile::fake()->image("new_img_{$i}.jpg");
        }

        $updateData = ProductSaveData::from([
            'category_id' => new CategoryId($category->id),
            'name' => 'Updated Name',
            'price' => 150,
            'images' => $newImages,
        ]);

        $updatedProduct = $service->updateProduct($product, $updateData);

        $updatedProduct->refresh();

        $this->assertCount(10, $updatedProduct->images);

        $savedImage = $updatedProduct->images->last();
        Storage::disk('minio')->assertExists($savedImage->path);

        $extraImage = UploadedFile::fake()->image("extra.jpg");
        $extraData = ProductSaveData::from([
            'category_id' => new CategoryId($category->id),
            'name' => 'Name',
            'price' => 100,
            'images' => [$extraImage],
        ]);

        $service->updateProduct($updatedProduct, $extraData);

        $this->assertCount(10, $updatedProduct->refresh()->images);
    }

    #[Test]
    public function testThrowsExceptionIfImageUploadFails(): void
    {
        $service = app(ProductService::class);
        $product = Product::factory()->create();

        $failingFile = Mockery::mock(UploadedFile::class);
        $failingFile->shouldReceive('store')->andReturn(false);

        $data = new UploadImageData($failingFile, false, 0);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to upload image to storage');

        $service->addImage($product, $data);
    }

    #[Test]
    public function testHandleImagesSkipsNullValuesInArray(): void
    {
        Storage::fake('minio');

        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(ProductService::class);
        $category = Category::factory()->create();

        $createData = ProductSaveData::from([
            'category_id' => new CategoryId($category->id),
            'name' => 'Name',
            'price' => 100,
            'images' => [
                UploadedFile::fake()->image("valid.jpg"),
                null,
            ],
        ]);

        $product = $service->createProduct($createData);
        $product->refresh();

        $this->assertCount(1, $product->images);
    }
}
