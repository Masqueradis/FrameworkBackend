<?php

namespace App\Http\Controllers\Web;

use App\Models\Product;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\View\View;

class ProductController
{
    public function show(Product $product, CommentRepositoryInterface $commentRepo): View
    {
        $product->load('images');

        $images = $product->images
            ->sortByDesc('is_primary')
            ->sortBy('position')
            ->values();

        $comments = $commentRepo->getApprovedForProduct($product->id);

        $userComment = auth()->check()
            ? $commentRepo->findByUserAndProduct((int) auth()->id(), $product->id)
            : null;

        return view('products.show', compact('product', 'images', 'comments', 'userComment'));
    }
}
