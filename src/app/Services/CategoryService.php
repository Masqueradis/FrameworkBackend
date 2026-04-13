<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CategorySaveData;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\ValueObjects\CategoryId;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

readonly class CategoryService
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    /** @return Collection<int, Category> */
    public function getAllCategories(): Collection
    {
        return $this->categoryRepository->getAll();
    }

    /** @return Collection<int, Category> */
    public function getRootCategories(): Collection
    {
        return $this->categoryRepository->getAll();
    }

    public function createCategory(CategorySaveData $data): Category
    {
        return $this->categoryRepository->create([
            'name' => $data->name,
            'slug' => Str::slug($data->name) . '-' . uniqid(),
            'parent_id' => $data->parent_id,
        ]);
    }

    public function updateCategory(Category $category, CategorySaveData $data): Category
    {
        return $this->categoryRepository->update($category, [
            'name' => $data->name,
            'parent_id' => $data->parent_id,
        ]);
    }

    public function deleteCategory(Category $category): void
    {
        $this->categoryRepository->delete($category);
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator<int, Category>
     */
    public function getPaginatedCategoriesWithParent(int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryRepository->getPaginatedWithParent($perPage);
    }

    /**
     * @param CategoryId|null $excludeId
     * @return Collection<int, Category>
     */
    public function getCategoriesForDropdown(?CategoryId $excludeId = null): Collection
    {
        return $this->categoryRepository->getForDropdown($excludeId);
    }
}
