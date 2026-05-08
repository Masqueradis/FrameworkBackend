<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Checkout\CheckoutDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\EmptyCartException;
use App\Models\Cart;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Gateways\Contracts\PaymentGatewayInterface;

readonly class CheckoutService
{
    public function __construct(
        private CartRepositoryInterface  $cartRepository,
        private OrderRepositoryInterface $orderRepository,
        private PaymentGatewayInterface $paymentGateway,
    ) {}

    /**
     * @throws EmptyCartException
     */
    public function process(CheckoutDTO $data, Cart $cart): Order
    {
        if($cart->items->isEmpty()) {
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

        $order = $this->orderRepository->createWithItems($orderData, $cart->items);

        $this->cartRepository->clearCart($cart->id);

        return $order;
    }

    public function processPayment(Order $order, string $paymentToken, string $provider): void
    {
        $result = $this->paymentGateway->charge($order, $paymentToken);

        $order->payments()->create([
            'provider' => $provider,
            'transaction_id' => $result->transactionId,
            'amount_cents' => $order->total_amount_cents,
            'status' => $result->isSuccess ? PaymentStatus::Paid : PaymentStatus::Failed,
        ]);

        if ($result->isSuccess) {
            $order->update(['status' => OrderStatus::Processing]);
        } else {
            $order->update(['status' => OrderStatus::Cancelled]);
        }
    }

    public function handleWebhook(Order $order, bool $isSuccess, string $transactionId, string $provider): void
    {
        $order->payments()->create([
            'provider' => $provider,
            'transaction_id' => $transactionId,
            'amount_cents' => $order->total_amount_cents,
            'status' => $isSuccess ? PaymentStatus::Paid : PaymentStatus::Failed,
        ]);

        if($isSuccess) {
            $order->update(['status' => OrderStatus::Processing]);
        } else {
            $order->update(['status' => OrderStatus::Cancelled]);
        }
    }
}
