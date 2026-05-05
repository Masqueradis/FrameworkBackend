<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    /**
     * @param array<string, mixed> $orderData
     * @param Collection<int, CartItem> $cartItems
     */
    public function createWithItems(array $orderData, Collection $cartItems): Order;
}
