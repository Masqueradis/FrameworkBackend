<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductAttributeService
{
    /**
     * @param Collection<int, Product> $products
     * @return array<string, array<int, mixed>>
     */
    public function extractUniqueAttributes(Collection $products): array
    {
        $attributes = [];

        foreach($products->pluck('attributes')->filter() as $productAttributes) {
            foreach($productAttributes as $key => $value) {
                $attributes[$key] = array_merge($attributes[$key] ?? [], (array) $value);
            }
        }
        return collect($attributes)
            ->map(fn (array $values) => collect($values)->unique()->sort()->values()->all())
            ->all();
    }
}
