<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Cart;
use App\Models\CartItem;
use App\ValueObjects\Cart\Money;

interface CartRepositoryInterface
{
    public function findOrCreate(?int $userId, ?string $sessionId): Cart;
    public function addOrUpdateItem(Cart $cart, int $productId, int $quantity, Money $price): CartItem;
    public function removeItem(int $cartItemId): void;
}
