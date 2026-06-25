<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\DTO\Checkout\PaymentWebhookDTO;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Gateways\Strategy\GatewayStrategyInterface;
use App\ValueObjects\Cart\Money;
use Exception;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeGateway implements GatewayStrategyInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutUrl(Order $order): string
    {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $this->buildLineItems($order),
            'mode' => 'payment',
            'expires_at' => time() + 1800,
            'success_url' => route('checkout.result').'?status=success',
            'cancel_url' => url('/profile'),
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
            'customer_email' => $order->customer_email,
        ]);

        return $session->url ?? '';
    }

    public function verifyWebhook(string $payload, string $signature): PaymentWebhookDTO
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            throw new Exception('Invalid Stripe signature', Response::HTTP_BAD_REQUEST);
        }

        /** @var Session $session */
        $session = $event->data->object;
        $type = $event->type;
        $orderId = (int) ($session->metadata->order_id ?? 0);

        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'])) {

            if ($session->payment_status !== 'paid') {
                throw new Exception('Payment not completed yet', Response::HTTP_OK);
            }

            $status = PaymentStatus::Success;

        } elseif (in_array($type, ['checkout.session.expired', 'checkout.session.async_payment_failed'])) {
            $status = PaymentStatus::Failed;
        } else {
            throw new Exception('Ignored event type', Response::HTTP_OK);
        }

        if ($status === PaymentStatus::Success) {
            $order = Order::find($orderId);

            if ($order) {
                /** @var mixed $rawTotal */
                $rawTotal = $order->total_amount_cents;

                $orderTotalCents = $rawTotal instanceof Money
                    ? $rawTotal->getCents()
                    : (int) $rawTotal;

                if ((int) $session->amount_total !== $orderTotalCents) {
                    $status = PaymentStatus::Failed;
                }
            }
        }

        return new PaymentWebhookDTO(
            orderId: $orderId,
            transactionId: (string) ($session->payment_intent ?? $session->id),
            provider: PaymentProvider::Stripe,
            status: $status
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function buildLineItems(Order $order): array
    {
        $items = [];
        $order->load('items.product');

        foreach ($order->items as $item) {
            /** @var Money $priceObj */
            $priceObj = $item->price_cents;
            $items[] = [
                'price_data' => [
                    'currency' => strtolower($priceObj->getCurrency()),
                    'unit_amount' => $priceObj->getCents(),
                    'product_data' => [
                        'name' => $item->product->name ?? 'Unknown Product',
                    ],
                ],
                'quantity' => $item->quantity,
            ];
        }

        return $items;
    }
}
