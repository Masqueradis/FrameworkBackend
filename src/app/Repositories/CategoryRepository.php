<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\ValueObjects\Id\CategoryId;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    public function findById(CategoryId $id): ?Category
    {
        return Category::find($id->value);
    }

    /** @return Collection<int, Category> */
    public function getAll(): Collection
    {
        return Category::orderBy('name')->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function getRoots(): Collection
    {
        return Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator<int, Category>
     */
    public function getPaginatedWithParent(int $perPage = 15): LengthAwarePaginator
    {
        return Category::with('parent')->paginate($perPage);
    }

    /**
     * @param CategoryId|null $excludeId
     * @return Collection<int, Category>
     */
    public function getForDropdown(?CategoryId $excludeId = null): Collection
    {
        $query = Category::query();

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId->value);
        }

        return $query->get();
    }
}
