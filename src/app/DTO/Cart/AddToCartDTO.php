<?php

declare(strict_types=1);

namespace App\DTO\Cart;

use App\ValueObjects\Cart\CartQuantity;

class AddToCartDTO
{
    public function __construct(
        public int $productId,
        public CartQuantity $quantity,
    ) {}
}
