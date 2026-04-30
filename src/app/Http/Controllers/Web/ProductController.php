<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\View\View;

class ProductController
{
    public function show(Product $product): View
    {
        $product->load('images');

        $images = $product->images
            ->sortByDesc('is_primary')
            ->sortBy('position')
            ->values();

        return view('products.show', compact('product', 'images'));
    }
}
