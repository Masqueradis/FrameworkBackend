<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CategorySaveData;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function getAllCategories(): Collection
    {
        return Category::orderBy('name')->get();
    }

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
            'parent_id' => $data->parentId,
        ]);

        return $category;
    }

    public function deleteCategory(Category $category): void
    {
        $category->delete();
    }
}
