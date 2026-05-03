<?php

declare(strict_types=1);

namespace App\DTO\Cart;

use App\ValueObjects\Cart\CartQuantity;

class UpdateCartItemDTO
{
    public function __construct(
        public int $cartItemId,
        public CartQuantity $quantity,
    ) {}
}
