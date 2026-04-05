<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CategorySaveData;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CategoryService
{
    /** @return Collection<int, Category> */
    public function getAllCategories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    /** @return Collection<int, Category> */
    public function getRootCategories(): Collection
    {
        return Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();
    }

    public function createCategory(CategorySaveData $data): Category
    {
        return Category::create([
            'name' => $data->name,
            'slug' => Str::slug($data->name) . '-' . uniqid(),
            'parent_id' => $data->parent_id,
        ]);
    }

    public function updateCategory(Category $category, CategorySaveData $data): Category
    {
        $category->update([
            'name' => $data->name,
            'parent_id' => $data->parent_id,
        ]);

        return $category;
    }

    public function deleteCategory(Category $category): void
    {
        $category->delete();
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator<int, Category>
     */
    public function getPaginatedCategoriesWithParent(int $perPage = 15): LengthAwarePaginator
    {
        return Category::with('parent')->paginate($perPage);
    }

    /**
     * @param int|null $excludeId
     * @return Collection<int, Category>
     */
    public function getCategoriesForDropdown(?int $excludeId = null): Collection
    {
        $query = Category::query();

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }
}
