<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\ValueObjects\Cart\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function createWithItemsAndDeductStock(array $orderData, Collection $cartItems): Order
    {
        return DB::transaction(function () use ($orderData, $cartItems) {
            $productIds = $cartItems->pluck('product_id')->toArray();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            foreach($cartItems as $item) {
                $product = $products->get($item->product_id);

                if(!$product || $product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Not enough stock for {$item->product?->name}.",
                    ]);
                }
                $product->decrement('stock', $item->quantity);
            }

            return $this->createWithItems($orderData, $cartItems);
        });
    }

    public function restoreStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->load('items.product');

            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }
        });
    }

    public function addPayment(Order $order, array $paymentData): Payment
    {
        return $order->payments()->create($paymentData);
    }

    public function updateStatus(Order $order, OrderStatus $status): bool
    {
        return $order->update(['status' => $status]);
    }

    public function chunkOrdersByDateRange(string $dateFrom, string $dateTo, int $chunkSize, callable $callback): void
    {
        Order::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->chunk($chunkSize, $callback);
    }
}
