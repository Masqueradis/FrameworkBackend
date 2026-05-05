<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\ValueObjects\Cart\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function createWithItems(array $orderData, Collection $cartItems): Order
    {
        return DB::transaction(function () use ($orderData, $cartItems) {
            $order = Order::create($orderData);

            $itemsData = $cartItems->map(function ($cartItem) {
                return [
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name ?? 'Unknown Product',
                    'quantity' => $cartItem->quantity,
                    'price_cents' => $cartItem->price->getCents(),
                ];
            });

            $order->items()->createMany($itemsData->toArray());

            return $order;
        });
    }
}
