<?php

declare (strict_types=1);

namespace App\Services;

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

        return $query->paginate(15);
    }
}
