<?php

declare (strict_types=1);

namespace App\Services;

use App\DTO\Product\ProductIndexDTO;
use App\DTO\Product\ProductSaveDTO;
use App\DTO\Product\UploadImageDTO;
use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\ProductImageRepository;
use App\Repositories\ProductRepository;
use App\ValueObjects\Id\CategoryId;
use Illuminate\Http\UploadedFile;
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
     * @param ProductIndexDTO $data
     * @return LengthAwarePaginator<int, Product>
     */
    public function getFilteredProducts(ProductIndexDTO $data): LengthAwarePaginator
    {
        return $this->productRepository->getFiltered([
            'category_id' => $data->categoryId?->value,
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

    public function createProduct(ProductSaveDTO $data): Product
    {
        $product = $this->productRepository->create([
            'user_id' => auth()->id(),
            'category_id' => $data->categoryId->value,
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

    public function updateProduct(Product $product, ProductSaveDTO $data): Product
    {
        $updateData = [
            'category_id' => $data->categoryId->value,
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

    public function addImage(Product $product, UploadImageDTO $data): ProductImage
    {
        $path = $data->image->store('products', 'minio');

        if ($path === false) {
            throw new \Exception('Failed to upload image to storage');
        }

        return $this->productImageRepository->createForProduct(
            $product,
            $path,
            $data->is_primary,
            $data->position,
        );
    }

    /**
     * @param Product $product
     * @param array<int, UploadedFile|null>|null $images
     * @param int $startPosition
     * @return void
     */
    private function handleImages(Product $product, ?array $images, int $startPosition = 0): void
    {
        if (empty($images)) {
            return;
        }

        $maxImagesLimit = 10;
        $allowedToUpload = $maxImagesLimit - $startPosition;

        if ($allowedToUpload <= 0) {
            return;
        }

        $imagesToProcess = array_slice($images, 0, $allowedToUpload);

        foreach ($imagesToProcess as $index => $image) {
            if ($image === null) {
                continue;
            }
            $imageData = new UploadImageDTO(
                image: $image,
                is_primary: $startPosition === 0 && $index === 0,
                position: $startPosition + $index,
            );

            $this->addImage($product, $imageData);
        }
    }
}
