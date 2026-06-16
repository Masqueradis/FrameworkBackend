<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\DTO\Checkout\PaymentWebhookDTO;
use App\Services\Gateways\Strategy\GatewayStrategyInterface;
use App\ValueObjects\Cart\Money;
use Exception;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use App\Models\Order;
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
            'success_url' => route('checkout.result') . '?status=success',
            'cancel_url' => route('checkout.index'),
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
            'customer_email' => $order->customer_email,
        ]);

        return $session->url ?? '';
    }

    public function verifyWebhook(Request $request): PaymentWebhookDTO
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            throw new Exception('Invalid Stripe signature', Response::HTTP_BAD_REQUEST);
        }

        if ($event->type !== 'checkout.session.completed') {
            throw new Exception('Ignored event type', Response::HTTP_OK);
        }

        /** @var Session $session */
        $session = $event->data->object;

        return new PaymentWebhookDTO(
            orderId: (int) ($session->metadata->order_id ?? 0),
            transactionId: (string) ($session->payment_intent ?? $session->id),
            provider: 'stripe',
            status: 'success'
        );
    }

    /**
     * @param Order $order
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
                    'currency' => 'usd',
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
