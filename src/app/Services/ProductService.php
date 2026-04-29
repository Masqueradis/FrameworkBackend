<?php

declare (strict_types=1);

namespace App\Services;

use App\Data\ProductIndexData;
use App\Data\ProductSaveData;
use App\Data\UploadImageData;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\ProductImageRepository;
use App\Repositories\ProductRepository;
use App\ValueObjects\CategoryId;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

readonly class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductAttributeService $attributeService,
        private ProductImageRepository $productImageRepository,
    ) {}

    /**
     * @param ProductIndexData $data
     * @return LengthAwarePaginator<int, Product>
     */
    public function getFilteredProducts(ProductIndexData $data): LengthAwarePaginator
    {
       return $this->productRepository->getFiltered([
           'category_id' => $data->categoryId,
           'min_price' => $data->minPrice,
           'max_price' => $data->maxPrice,
           'search' => $data->search,
           'attributes' => $data->attributes,
       ]);
    }

    /**
     * @param CategoryId|null $categoryId
     * @return array<string, mixed>
     */
    public function getFilteredData(?CategoryId $categoryId): array
    {
        $products = $this->productRepository->getActiveProductsByCategory($categoryId);

        return [
            'min_price' => floor((float) ($products->min('price') ?? 0)),
            'max_price' => ceil((float) ($products->max('price') ?? 0)),
            'attributes' => $this->attributeService->extractUniqueAttributes($products),
        ];
    }

    public function createProduct(ProductSaveData $data): Product
    {
        return $this->productRepository->create([
            'user_id' => auth()->id(),
            'category_id' => $data->categoryId,
            'name' => $data->name,
            'slug' => Str::slug($data->name) . '-' . uniqid(),
            'sku' => $data->sku ?? 'SKU-' . strtoupper(Str::random(8)),
            'description' => $data->description,
            'price' => $data->price,
            'stock' => $data->stock,
            'available' => $data->available,
            'attributes' => $data->attributes,
        ]);

        $this->handleImages($product, $data->images);

        return $product;
    }

    public function updateProduct(Product $product, ProductSaveData $data): Product
    {
        $updateData = [
            'category_id' => $data->categoryId,
            'name' => $data->name,
            'description' => $data->description,
            'price' => $data->price,
            'stock' => $data->stock,
            'available' => $data->available,
            'attributes' => $data->attributes,
        ];

        if ($data->sku !== null) {
            $updateData['sku'] = $data->sku;
        }

        $updatedProduct = $this->productRepository->update($product, $updateData);

        $currentImagesCount = $product->images()->count();
        $this->handleImages($product, $data->images, $currentImagesCount);

        return $updatedProduct;
    }

    public function deleteProduct(Product $product): void
    {
        $this->productRepository->delete($product);
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedProductsForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getPaginatedForAdmin($perPage);
    }

    public function addImage(Product $product, UploadImageData $data): ProductImage
    {
        $path = $data->image->store('products', 'minio');

        return $this->productImageRepository->createForProduct(
            $product,
            $path,
            $data->is_primary,
            $data->position,
        );
    }

    private function handleImages(Product $product, ?array $images, int $startPosition = 0): void
    {
        if(empty($images)) {
            return;
        }

        foreach ($images as $index => $image) {
            $imageData = new UploadImageData(
                image: $image,
                is_primary: $startPosition === 0 && $index === 0,
                position: $startPosition + $index,
            );

            $this->addImage($product, $imageData);
        }
    }
}
