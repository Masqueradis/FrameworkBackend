<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Filters\ProductFilter;
use App\Models\Category;
use App\Models\Product;
use App\ValueObjects\CategoryId;
use App\ValueObjects\ProductId;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function findById(ProductId $id): ?Product
    {
        return Product::find($id->value);
    }

    /**
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Product>
     */
    public function getFiltered(array $filters, int $perPage = 9): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'images'])
            ->where('available', true)
            ->filter(new ProductFilter($filters))
            ->paginate($perPage);
    }

    /**
     * @param CategoryId|null $categoryId
     * @return Collection<int, Product>
     */
    public function getActiveProductsByCategory(?CategoryId $categoryId): Collection
    {
        $query = Product::where('available', true);

        if ($categoryId) {
            $categoryIds = Category::where('id', $categoryId->value)
                ->orWhere('parent_id', $categoryId->value)
                ->pluck('id');
            $query->whereIn('category_id', $categoryIds);
        }

        return $query->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'images'])
            ->orderBy('id', 'asc')
            ->paginate($perPage);
    }
}
