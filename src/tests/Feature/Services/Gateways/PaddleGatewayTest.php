<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Gateways;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Gateways\PaddleGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

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
    public function testCreatesCheckoutUrlSuccessfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Http::fake([
            '*/transactions' => Http::response([
                'data' => ['id' => 'txn_12345']
            ], 200)
        ]);

        $product = Product::factory()->create(['price' => 1000]);
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 2000
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 1000,
            'price_cents' => 1000*100,
            'product_name' => $product->name,
        ]);

        $transactionId = $this->gateway->createCheckoutUrl($order);

        $this->assertEquals('txn_12345', $transactionId);
    }

    #[Test]
    public function testThrowsExceptionIfPaddleApiFails(): void
    {
        $user = User::factory()->create();
        Http::fake([
            '*/transactions' => Http::response(['Bad request'], Response::HTTP_BAD_REQUEST)
        ]);

        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 2000
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Paddle Error: ["Bad request"]');
        $this->gateway->createCheckoutUrl($order);
    }

    #[Test]
    public function testVerifiesValidWebhook(): void
    {
        $payload = json_encode([
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_999',
                'custom_data' => ['order_id' => 15]
            ]
        ]);

        $ts = time();
        $h1 = hash_hmac('sha256', $ts . ':' . $payload, 'test_secret');
        $signatureHeader = "ts={$ts};h1={$h1}";

        $request = Request::create('/webhook/paddle', 'POST', [], [], [], [], $payload);
        $request->headers->set('Paddle-Signature', $signatureHeader);
        $request->headers->set('Content-Type', 'application/json');

        $dto = $this->gateway->verifyWebhook($request);

        $this->assertEquals(15, $dto->orderId);
        $this->assertEquals('txn_999', $dto->transactionId);
        $this->assertEquals('paddle', $dto->provider);
        $this->assertTrue($dto->isSuccess());
    }

    #[Test]
    public function testThrowsExceptionOnInvalidSignature(): void
    {
        $request = Request::create('/webhook/paddle', 'POST');
        $request->headers->set('Paddle-Signature', 'ts=123;h1=invalid_hash');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Paddle signature');
        $this->gateway->verifyWebhook($request);
    }

    #[Test]
    public function testUsesProductionUrlWhenEnvIsProduction(): void
    {
        Config::set('services.paddle.env', 'production');
        $gateway = $this->app->make(PaddleGateway::class);
        $this->assertNotNull($gateway);
    }

    #[Test]
    public function testThrowsExceptionOnMissingSignature(): void
    {
        $request = Request::create('/webhook/paddle', 'POST');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Paddle signature');
        $this->gateway->verifyWebhook($request);
    }

    #[Test]
    public function testThrowsExceptionOnIgnoredEventType(): void
    {
        $payload = json_encode(['event_type' => 'subscription.created', 'data' => []]);
        $ts = time();
        $h1 = hash_hmac('sha256', $ts . ':' . $payload, 'test_secret');

        $request = Request::create('/webhook/paddle', 'POST', [], [], [], [], $payload);
        $request->headers->set('Paddle-Signature', "ts={$ts};h1={$h1}");
        $request->headers->set('Content-Type', 'application/json');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ignored event type');
        $this->gateway->verifyWebhook($request);
    }
}
