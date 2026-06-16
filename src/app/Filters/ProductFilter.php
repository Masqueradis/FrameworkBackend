<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\Product;
use App\ValueObjects\Id\CategoryId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends QueryFilter<Product>
 */
class ProductFilter extends QueryFilter
{
    /**
     * @param int|string $id
     */
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
        $term = '%' . $search . '%';

        $this->builder->where(function ($query) use ($term) {
            $query->where('name', 'like', $term)
                ->orWhere('description', 'like', $term);
        });
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function attributes(array $attributes): void
    {
        foreach ($attributes as $attribute => $value) {
            $this->builder->whereIn("attributes->{$attribute}", (array) $value);
        }
    }
}
