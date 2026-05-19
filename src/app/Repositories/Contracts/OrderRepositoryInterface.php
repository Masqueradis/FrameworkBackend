<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\OrderStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    /**
     * @param array<string, mixed> $orderData
     * @param Collection<int, CartItem> $cartItems
     * @return Order
     */
    public function createWithItems(array $orderData, Collection $cartItems): Order;
    /**
     * @param array<string, mixed> $orderData
     * @param Collection<int, CartItem> $cartItems
     * @return Order
     */
    public function createWithItemsAndDeductStock(array $orderData, Collection $cartItems): Order;
    public function restoreStock(Order $order): void;
    /**
     * @param Order $order
     * @param array<string, mixed> $paymentData
     * @return Payment
     */
    public function addPayment(Order $order, array $paymentData): Payment;
    public function updateStatus(Order $order, OrderStatus $status): bool;
    public function chunkOrdersByDateRange(string $dateFrom, string $dateTo, int $chunkSize, callable $callback): void;
}
