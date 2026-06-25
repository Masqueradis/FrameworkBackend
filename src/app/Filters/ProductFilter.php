<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\ProductAttribute;
use App\Models\Product;
use App\ValueObjects\Id\CategoryId;

/**
 * @extends QueryFilter<Product>
 */
class ProductFilter extends QueryFilter
{
    protected array $allowedFilters = [
        'category_id',
        'min_price',
        'max_price',
        'search',
        'attributes',
    ];

    public function categoryId(int|string $id): void
    {
        $categoryIdVO = new CategoryId((int) $id);

        $this->builder->where('category_id', $categoryIdVO->value);
    }

    public function minPrice(float|int|string $price): void
    {
        $this->builder->where('price', '>=', (float) $price);
    }

    public function maxPrice(float|int|string $price): void
    {
        $this->builder->where('price', '<=', (float) $price);
    }

    public function search(string $search): void
    {
        $this->builder->whereFullText(['name', 'description'], $search);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function attributes(array $attributes): void
    {
        foreach ($attributes as $attribute => $value) {
            if (ProductAttribute::tryFrom($attribute)) {
                $this->builder->whereIn("attributes->{$attribute}", (array) $value);
            }
        }
    }
}
