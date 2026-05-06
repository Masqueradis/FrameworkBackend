<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Payment\CheckoutDTO;
use App\Enums\OrderStatus;
use App\Exceptions\EmptyCartException;
use App\Models\Cart;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;

readonly class PaymentService
{
    public function __construct(
        private CartRepositoryInterface  $cartRepository,
        private OrderRepositoryInterface $orderRepository,
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
}
