<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {

        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        $query = Product::with('category')->where('available', true);

        if ($request->has('category_id')) {
            $categoryId = $request->category_id;

            $categoryIds = Category::where('id', $categoryId)
                ->orWhere('parent_id', $categoryId)
                ->pluck('id');
            $query->whereIn('category_id', $categoryIds);
        }

        $products = $query->paginate(12)->withQueryString();

        return view('catalog.index', compact('categories', 'products'));
    }
}
