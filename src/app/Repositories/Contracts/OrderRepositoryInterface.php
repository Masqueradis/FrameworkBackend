<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $orderData
     * @param  Collection<int, CartItem>  $cartItems
     */
    public function createWithItems(array $orderData, Collection $cartItems): Order;

    /**
     * @param  array<string, mixed>  $orderData
     * @param  Collection<int, CartItem>  $cartItems
     */
    public function createWithItemsAndDeductStock(array $orderData, Collection $cartItems): Order;

    public function restoreStock(Order $order): void;

    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function addPayment(Order $order, array $paymentData): Payment;

    public function updateStatus(Order $order, string $status): bool;

    public function chunkOrdersByDateRange(string $dateFrom, string $dateTo, int $chunkSize, callable $callback): void;

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function getPaginatedUserOrders(int $userId, int $perPage = 5): LengthAwarePaginator;
}
