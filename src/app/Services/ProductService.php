<?php

declare (strict_types=1);

namespace App\Services;

use App\Data\ProductIndexData;
use App\Data\ProductSaveData;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * @param ProductIndexData $data
     * @return LengthAwarePaginator<int, Product>
     */
    public function getFilteredProducts(ProductIndexData $data): LengthAwarePaginator
    {
        $query = Product::query()->with('category')->where('available', true);

        if ($data->categoryId) {
            $query->where('category_id', $data->categoryId);
        }

        if ($data->minPrice) {
            $query->where('price', '>=', $data->minPrice);
        }

        if ($data->maxPrice) {
            $query->where('price', '<=', $data->maxPrice);
        }

        if ($data->search) {
            $query->where(function ($q) use ($data) {
                $searchTerm = '%' . $data->search . '%';
                $q->where('name', 'ilike', $searchTerm)
                    ->orWhere('description', 'ilike', $searchTerm);
            });
        }

        if ($data->attributes) {
            foreach ($data->attributes as $attribute => $value) {
                if (is_array($value)) {
                    $query->whereIn("attributes->$attribute", $value);
                } else {
                    $query->where("attributes->$attribute", $value);
                }
            }
        }

        return $query->paginate(9);
    }

    /**
     * @param int|null $categoryId
     * @return array<string, mixed>
     */
    public function getFilteredData(?int $categoryId): array
    {
        $query = Product::where('available', true);

        if ($categoryId) {
            $categoryIds = Category::where('id', $categoryId)
                ->orWhere('parent_id', $categoryId)
                ->pluck('id');
            $query->whereIn('category_id', $categoryIds);
        }

        $minPrice = floor((float) ($query->min('price') ?? 0));
        $maxPrice = ceil((float) ($query->max('price') ?? 0));

        $attributes = [];
        $products = $query->select('attributes')->get();

        foreach ($products as $product) {
            if (!is_array($product->attributes)) {
                continue;
            }

            foreach ($product->attributes as $key => $value) {
                if (!isset($attributes[$key])) {
                    $attributes[$key] = [];
                }

                if (!in_array($value, $attributes[$key])) {
                    $attributes[$key][] = $value;
                }
            }
        }

        foreach ($attributes as $key => $value) {
            sort($attributes[$key]);
        }

        return [
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'attributes' => $attributes,
        ];
    }

    public function createProduct(ProductSaveData $data): Product
    {
        return Product::create([
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

        $product->update($updateData);

        return $product;
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedProductsForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Product::with('category')->latest()->paginate($perPage);
    }
}
