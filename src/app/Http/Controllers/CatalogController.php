<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CategoryService;
use App\Services\ProductService;
use App\Models\Category;
use App\Models\Product;
use App\DTO\ProductIndexData;
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
        $categories = $this->categoryService->getRootCategories();

        $products = $this->productService->getFilteredProducts($data);
        $products->withQueryString();

        $filtersData = $this->productService->getFilteredData($data->categoryId);

        return view('catalog.index', compact('categories', 'products', 'filtersData'));
    }
}
