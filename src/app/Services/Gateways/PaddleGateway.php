<?php

namespace App\Services\Gateways;

use App\DTO\Checkout\PaymentWebhookDTO;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Gateways\Strategy\GatewayStrategyInterface;
use App\ValueObjects\Cart\Money;
use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class PaddleGateway implements GatewayStrategyInterface
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.paddle.env') === 'sandbox'
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';
    }

    public function createCheckoutUrl(Order $order): string
    {
        $response = Http::withToken(config('services.paddle.api_key'))
            ->post("{$this->apiUrl}/transactions", [
                'items' => $this->buildItems($order),
                'custom_data' => ['order_id' => $order->id],
                'customer_info' => ['email' => $order->customer_email],
                'checkout' => [
                    'success_url' => route('checkout.result').'?status=success',
                    'cancel_url' => route('checkout.cancel', ['order' => $order->id]),
                ],
            ]);

        if (! $response->successful()) {
            throw new Exception('Paddle Error: '.$response->body());
        }

        return $response->json('data.id');
    }

    public function verifyWebhook(string $payload, string $signature): PaymentWebhookDTO
    {
        if (! $signature) {
            throw new Exception('Invalid Paddle signature', Response::HTTP_BAD_REQUEST);
        }

        $parts = explode(';', $signature);
        if (count($parts) < 2) {
            throw new Exception('Malformed Paddle signature', Response::HTTP_BAD_REQUEST);
        }

        $ts = str_replace('ts=', '', $parts[0]);
        $h1 = str_replace('h1=', '', $parts[1]);

        $signedPayload = $ts.':'.$payload;
        $secret = config('services.paddle.webhook_secret');
        $expectedH1 = hash_hmac('sha256', $signedPayload, $secret);

        if (! hash_equals($h1, $expectedH1)) {
            throw new Exception('Invalid Paddle signature', Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($payload, true);
        $eventType = $data['event_type'] ?? '';
        $orderId = (int) $data['data']['custom_data']['order_id'];

        if ($eventType === 'transaction.completed') {
            $status = PaymentStatus::Success;
        } elseif (in_array($eventType, ['transaction.canceled', 'transaction.payment_failed'])) {
            $status = PaymentStatus::Failed;
        } else {
            throw new Exception('Ignored event type', Response::HTTP_OK);
        }

        return new PaymentWebhookDTO(
            orderId: $orderId,
            transactionId: (string) $data['data']['id'],
            provider: PaymentProvider::Paddle,
            status: $status
        );
    }

    /**
     * @return array<int, mixed>
     */
    private function buildItems(Order $order): array
    {
        $items = [];
        $order->load('items.product');

        foreach ($order->items as $item) {
            /** @var Money $priceObj */
            $priceObj = $item->price_cents;
            $items[] = [
                'price' => [
                    'description' => $item->product->name ?? 'Unknown product',
                    'unit_price' => [
                        'amount' => (string) $priceObj->getCents(),
                        'currency_code' => strtoupper($priceObj->getCurrency()),
                    ],
                    'product' => [
                        'name' => $item->product->name ?? 'Unknown product',
                        'tax_category' => 'standard',
                    ],
                ],
                'quantity' => $item->quantity,
            ];
        }

        return $items;
    }
}
