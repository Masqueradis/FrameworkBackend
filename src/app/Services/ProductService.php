<?php

declare (strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\DTO\ProductFilterDTO;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function getFilteredProducts(ProductFilterDTO $dto): LengthAwarePaginator
    {
        $query = Product::query()->with('category')->where('available', true);

        if ($dto->categoryId) {
            $query->where('category_id', $dto->categoryId);
        }

        if ($dto->minPrice) {
            $query->where('price', '>=', $dto->minPrice);
        }

        if ($dto->maxPrice) {
            $query->where('price', '<=', $dto->maxPrice);
        }

        if ($dto->search) {
            $query->where('name', 'ilike', '%' . $dto->search . '%');
        }

        if ($dto->attributes) {
            foreach ($dto->attributes as $attribute => $value) {
                if (is_array($value)) {
                    $query->whereIn("attributes->$attribute", $value);
                }
                else {
                    $query->where("attributes->$attribute", $value);
                }
            }
        }

        return $query->paginate(15);
    }

    public function getFilteredData(?int $categoryId): array
    {
        $query = Product::where('available', true);

        if ($categoryId) {
            $categoryIds = Category::where('id', $categoryId)
                ->orWhere('parent_id', $categoryId)
                ->pluck('id');
            $query->whereIn('id', $categoryIds);
        }

        $minPrice = floor((float)($query->min('price') ?? 0));
        $maxPrice = ceil((float)($query->max('price') ?? 0));

        $attributes = [];
        $products = $query->select('attributes')->get();

        foreach ($products as $product) {
            if (!is_array($product->attributes)) continue;

            foreach ($product->attributes as $key => $value) {
                if(!isset($attributes[$key])) {
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
}
