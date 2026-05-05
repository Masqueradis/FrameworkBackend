<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTO\CategorySaveDTO;
use App\Http\Controllers\ApiController;
use App\Models\Category;
use App\Services\CategoryService;
use App\ValueObjects\Id\CategoryId;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminCategoryController extends ApiController
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    public function index(): View
    {
        $categories = $this->categoryService->getPaginatedCategoriesWithParent(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $categories = $this->categoryService->getCategoriesForDropdown();

        return view('admin.categories.form', compact('categories'));
    }

    public function store(CategorySaveDTO $data): RedirectResponse
    {
        $this->categoryService->createCategory($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $categories = $this->categoryService->getCategoriesForDropdown(
            $category->id ? new CategoryId($category->id) : null,
        );

        return view('admin.categories.form', compact('category', 'categories'));
    }

    public function update(Category $category, CategorySaveDTO $data): RedirectResponse
    {
        $this->categoryService->updateCategory($category, $data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categoryService->deleteCategory($category);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
