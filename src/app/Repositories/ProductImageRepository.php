<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductImage;

class ProductImageRepository
{
    public function createForProduct(Product $product, string $path, bool $isPrimary, int $position): ProductImage
    {
        /** @var ProductImage $image */
        $image = $product->images()->create([
            'path' => $path,
            'is_primary' => $isPrimary,
            'position' => $position,
        ]);

        return $image;
    }
}
