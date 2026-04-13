<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\Product;
use App\ValueObjects\CategoryId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductFilter extends QueryFilter
{
    /**
     * @param int|string $id
     * @return Builder<Model>
     */
    public function categoryId(int|string $id): Builder
    {
        $categoryIdVO = new CategoryId((int) $id);

        return $this->builder->where('category_id', $categoryIdVO->value);
    }

    public function minPrice(float|int|string $price): void
    {
        $this->builder->where('price', '>=', $price);
    }

    public function maxPrice(float|int|string $price): void
    {
        $this->builder->where('price', '<=', $price);
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
        foreach($attributes as $attribute => $value) {
            $this->builder->where("attributes->$attribute", (array) $value);
        }
    }
}
