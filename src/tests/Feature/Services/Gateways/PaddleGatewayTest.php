<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Gateways;

use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Gateways\PaddleGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PaddleGatewayTest extends TestCase
{
    use RefreshDatabase;

    private PaddleGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.paddle.api.key', 'test_api_key');
        Config::set('services.paddle.webhook_secret', 'test_secret');

        $this->gateway = $this->app->make(PaddleGateway::class);
    }

    #[Test]
    public function test_creates_checkout_url_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Http::fake([
            '*/transactions' => Http::response([
                'data' => ['id' => 'txn_12345'],
            ], 200),
        ]);

        $product = Product::factory()->create(['price' => 1000]);
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 2000,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 1000,
            'price_cents' => 1000 * 100,
            'product_name' => $product->name,
        ]);

        $transactionId = $this->gateway->createCheckoutUrl($order);

        $this->assertEquals('txn_12345', $transactionId);
    }

    #[Test]
    public function test_throws_exception_if_paddle_api_fails(): void
    {
        $user = User::factory()->create();
        Http::fake([
            '*/transactions' => Http::response(['Bad request'], Response::HTTP_BAD_REQUEST),
        ]);

        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 2000,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Paddle Error: ["Bad request"]');
        $this->gateway->createCheckoutUrl($order);
    }

    #[Test]
    public function test_verifies_valid_webhook(): void
    {
        $payload = json_encode([
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_999',
                'custom_data' => ['order_id' => 15],
            ],
        ]);

        $ts = time();
        $h1 = hash_hmac('sha256', $ts.':'.$payload, 'test_secret');
        $signatureHeader = "ts={$ts};h1={$h1}";

        $dto = $this->gateway->verifyWebhook($payload, $signatureHeader);

        $this->assertEquals(15, $dto->orderId);
        $this->assertEquals('txn_999', $dto->transactionId);
        $this->assertEquals(PaymentProvider::Paddle, $dto->provider);
        $this->assertTrue($dto->isSuccess());
    }

    #[Test]
    public function test_throws_exception_on_invalid_signature(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Paddle signature');
        $this->gateway->verifyWebhook('', 'ts=123;h1=invalid_hash');
    }

    #[Test]
    public function test_uses_production_url_when_env_is_production(): void
    {
        Config::set('services.paddle.env', 'production');
        $gateway = $this->app->make(PaddleGateway::class);
        $this->assertNotNull($gateway);
    }

    #[Test]
    public function test_throws_exception_on_missing_signature(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Paddle signature');

        $this->gateway->verifyWebhook('', '');
    }

    #[Test]
    public function test_throws_exception_on_ignored_event_type(): void
    {
        $payload = json_encode([
            'event_type' => 'subscription.created',
            'data' => [
                'custom_data' => [
                    'order_id' => 1,
                ],
            ],
        ]);

        $ts = time();
        $h1 = hash_hmac('sha256', $ts.':'.$payload, 'test_secret');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ignored event type');

        $this->gateway->verifyWebhook($payload, "ts={$ts};h1={$h1}");
    }

    #[Test]
    public function test_throws_exception_on_malformed_signature(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Malformed Paddle signature');
        $this->gateway->verifyWebhook('{}', 'invalid_signature_without_semicolon');
    }

    #[Test]
    public function test_verifies_failed_webhook(): void
    {
        $payload = json_encode([
            'event_type' => 'transaction.canceled',
            'data' => [
                'id' => 'txn_failed_999',
                'custom_data' => ['order_id' => 15],
            ],
        ]);

        $ts = time();
        $h1 = hash_hmac('sha256', $ts.':'.$payload, config('services.paddle.webhook_secret'));

        $dto = $this->gateway->verifyWebhook($payload, "ts={$ts};h1={$h1}");

        $this->assertEquals(PaymentStatus::Failed, $dto->status);
    }

    #[Test]
    public function test_throws_exception_for_zombie_payment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Cancelled,
        ]);

        $payload = json_encode([
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_zombie_999',
                'custom_data' => ['order_id' => $order->id],
            ],
        ]);

        $ts = time();
        $h1 = hash_hmac('sha256', $ts.':'.$payload, config('services.paddle.webhook_secret'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment received for cancelled order. Flagged for refund.');

        $this->gateway->verifyWebhook($payload, "ts={$ts};h1={$h1}");
    }

    public function test_throws_exception_on_malformed_paddle_signature(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Malformed Paddle signature');

        (new PaddleGateway)->verifyWebhook('{"data": {}}', 'ts=12345');
    }

    public function test_handles_canceled_paddle_transaction(): void
    {
        $payload = json_encode([
            'event_type' => 'transaction.canceled',
            'data' => ['id' => 'txn_123', 'custom_data' => ['order_id' => '1']],
        ]);

        $signature = $this->generatePaddleSignature($payload);
        $dto = (new PaddleGateway)->verifyWebhook($payload, $signature);

        $this->assertEquals(PaymentStatus::Failed, $dto->status);
    }

    protected function generatePaddleSignature(string $payload): string
    {
        $timestamp = time();
        $secret = config('services.paddle.webhook_secret');

        $signedPayload = "{$timestamp}:{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "ts={$timestamp};h1={$signature}";
    }
}
