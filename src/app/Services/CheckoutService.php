<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Checkout\CheckoutDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Exceptions\EmptyCartException;
use App\Models\Cart;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Exception;

readonly class CheckoutService
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
            throw new EmptyCartException();
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
            'status' => OrderStatus::Pending,
            'total_amount_cents' => $totalCents,
        ];

        $order = $this->orderRepository->createWithItemsAndDeductStock($orderData, $cart->items);

        OrderCreated::dispatch($order);

        return $order;
    }

    public function handleWebhook(Order $order, bool $isSuccess, string $transactionId, string $provider): void
    {
        $this->orderRepository->addPayment($order, [
            'provider' => $provider,
            'transaction_id' => $transactionId,
            'amount_cents' => $order->total_amount_cents,
            'status' => $isSuccess ? PaymentStatus::Paid : PaymentStatus::Failed,
        ]);

        if($isSuccess) {
            $this->orderRepository->updateStatus($order, OrderStatus::Processing);
            return;
        }

        $this->orderRepository->updateStatus($order, OrderStatus::Cancelled);
        $this->orderRepository->restoreStock($order);
    }
}
