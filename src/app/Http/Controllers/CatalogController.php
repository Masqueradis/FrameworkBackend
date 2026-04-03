<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ProductIndexData;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
    ) {}

    public function index(ProductIndexData $data): View
    {
        $products = $this->productService->getFilteredProducts($data);

        $filtersData = $this->productService->getFilteredData($data->categoryId);

        $categories = $this->categoryService->getRootCategories();

        return view('catalog.index', [
            'products' => $products,
            'filtersData' => $filtersData,
            'categories' => $categories,
        ]);
    }
}
