<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Models\Category;
use App\Models\Product;
use App\Http\Requests\ProductIndexRequest;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(ProductIndexRequest $request): View
    {
        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        $dto = $request->toDTO();

        $products = $this->productService->getFilteredProducts($dto);
        $products->withQueryString();
        return view('catalog.index', compact('categories', 'products'));
    }
}
