<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\ProductIndexDTO;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\ValueObjects\Id\CategoryId;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
    ) {}

    public function index(ProductIndexDTO $data): View
    {
        $products = $this->productService->getFilteredProducts($data);

        $categoryIdVO = $data->categoryId?->value ? new CategoryId((int) $data->categoryId->value) : null;

        $filtersData = $this->productService->getFilteredData($categoryIdVO);

        $categories = $this->categoryService->getRootCategories();

        return view('catalog.index', [
            'products' => $products,
            'filtersData' => $filtersData,
            'categories' => $categories,
        ]);
    }
}
