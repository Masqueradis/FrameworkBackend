<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Data\CategorySaveData;
use App\Data\ProductSaveData;
use App\Data\UploadImageData;
use App\Http\Controllers\ApiController;
use App\Models\Category;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

readonly class AdminProductController
{
    public function __construct(
        private ProductService  $productService,
        private CategoryService $categoryService,
    ) {}

    public function index(): View
    {
        $products = $this->productService->getPaginatedProductsForAdmin(15);
        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = $this->categoryService->getCategoriesForDropdown();
        return view('admin.products.form', compact('categories'));
    }

    public function store(ProductSaveData $data): RedirectResponse
    {
        $this->productService->createProduct($data);
        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    #[Can('update', 'product')]
    public function edit(Product $product): View
    {
        $categories = $this->categoryService->getCategoriesForDropdown();
        return view('admin.products.form', compact('product', 'categories'));
    }

    public function update(ProductSaveData $data, Product $product): RedirectResponse
    {
        $this->productService->updateProduct($product, $data);
        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->deleteProduct($product);
        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function uploadImage(Product $product, UploadImageData $data): JsonResponse
    {
        $image = $this->productService->addImage($product, $data);

        return response()->json([
            'message' => 'Image added successfully.',
            'image' => $image,
        ], Response::HTTP_OK);
    }
}
