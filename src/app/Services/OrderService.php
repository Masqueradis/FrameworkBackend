<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Checkout\CheckoutDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Events\PaymentCompleted;
use App\Exceptions\EmptyCartException;
use App\Models\Cart;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

readonly class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {}

    /**
     * @throws EmptyCartException
     */
    public function process(CheckoutDTO $data, Cart $cart): Order
    {
        if ($cart->items->isEmpty()) {
            throw new EmptyCartException;
        }

        $totalCents = $cart->items->sum(function ($item) {
            return $item->price->getCents() * $item->quantity;
        });

        $orderData = [
            'user_id' => $cart->user_id,
            'customer_name' => $data->customerName,
            'customer_email' => $data->customerEmail,
            'customer_phone' => $data->customerPhone,
            'shipping_address' => $data->shippingAddress,
            'status' => OrderStatus::Pending->value,
            'total_amount_cents' => $totalCents,
        ];

        $order = $this->orderRepository->createWithItemsAndDeductStock($orderData, $cart->items);

        $this->orderRepository->addPayment($order, [
            'provider' => $data->paymentProvider->value,
            'transaction_id' => null,
            'amount_cents' => $totalCents,
            'status' => PaymentStatus::Pending->value,
        ]);

        OrderCreated::dispatch($order);

        return $order;
    }

    public function handleWebhook(Order $order, bool $isSuccess, string $transactionId, string $provider): void
    {
        DB::transaction(function () use ($order, $isSuccess, $transactionId, $provider) {

            if ($order->payments()->where('transaction_id', $transactionId)->exists()) {
                return;
            }

            $payment = $this->orderRepository->addPayment($order, [
                'provider' => $provider,
                'transaction_id' => $transactionId,
                'amount_cents' => $order->total_amount_cents,
                'status' => $isSuccess ? PaymentStatus::Success->value : PaymentStatus::Failed->value,
            ]);

            if ($isSuccess) {
                $this->orderRepository->updateStatus($order, OrderStatus::Completed->value);

                PaymentCompleted::dispatch($payment);

                return;
            }

            /** @var mixed $rawStatus */
            $rawStatus = $order->status;
            $statusStr = $rawStatus instanceof \BackedEnum ? $rawStatus->value : (string) $rawStatus;

            if ($statusStr !== OrderStatus::Cancelled->value) {
                $this->orderRepository->updateStatus($order, OrderStatus::Cancelled->value);
                $this->orderRepository->restoreStock($order);
            }
        });
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function getUserOrdersHistory(int $userId, int $perPage = 5): LengthAwarePaginator
    {
        return $this->orderRepository->getPaginatedUserOrders($userId, $perPage);
    }
}
