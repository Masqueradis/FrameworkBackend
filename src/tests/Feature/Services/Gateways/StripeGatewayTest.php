<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Gateways;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Gateways\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Mockery;
use Stripe\ApiRequestor;
use Stripe\Checkout\Session;
use Stripe\HttpClient\ClientInterface;
use Stripe\Webhook;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StripeGatewayTest extends TestCase
{
    use RefreshDatabase;

    private StripeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.stripe.secret', 'sk_test_dummy_key');
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');
        $this->gateway = $this->app->make(StripeGateway::class);
    }

    public function tearDown(): void
    {
        Mockery::close();
        ApiRequestor::setHttpClient(null);
        parent::tearDown();
    }

    #[Test]
    public function testBuildsLineItemsCorrectly(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product', 'price' => 1500]);
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 4500
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'price_cents' => 1500,
            'product_name' => $product->name,
        ]);

        $lineItems = $this->gateway->buildLineItems($order);

        $this->assertCount(1, $lineItems);
        $this->assertEquals('usd', $lineItems[0]['price_data']['currency']);
        $this->assertEquals(1500, $lineItems[0]['price_data']['unit_amount']);
        $this->assertEquals('Test Product', $lineItems[0]['price_data']['product_data']['name']);
        $this->assertEquals(3, $lineItems[0]['quantity']);
    }

    #[Test]
    public function testThrowsExceptionOnInvalidWebhookSignature(): void
    {
        $request = Request::create('/webhooks/stripe', 'POST', [], [], [], [], '{"type":"checkout.session.completed"}');
        $request->headers->set('Stripe-Signature', 'invalid_signature');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Stripe signature');
        $this->gateway->verifyWebhook($request);
    }

    #[Test]
    public function testCreatesCheckoutUrl(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 4500
        ]);

        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn([
                json_encode(['url' => 'https://checkout.stripe.com/pay']),
                200,
                []
            ]);

        ApiRequestor::setHttpClient($mockClient);

        $url = $this->gateway->createCheckoutUrl($order);

        $this->assertEquals('https://checkout.stripe.com/pay', $url);
    }

    #[Test]
    public function testVerifiesValidWebhook(): void
    {
        $payload = json_encode ([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'metadata' => ['order_id' => '10'],
                    'payment_intent' => 'pi_123',
                ],
            ]
        ]);

        $request = Request::create('/webhooks/stripe', 'POST', [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', $this->generateStripeSignature($payload));

        $dto = $this->gateway->verifyWebhook($request);

        $this->assertEquals(10, $dto->orderId);
        $this->assertEquals('pi_123', $dto->transactionId);
        $this->assertTrue($dto->isSuccess());
    }

    #[Test]
    public function testIgnoresOtherWebhookEvents(): void
    {
        $payload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []]
        ]);

        $request = Request::create('/webhooks/stripe', 'POST', [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', $this->generateStripeSignature($payload));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ignored event type');
        $this->gateway->verifyWebhook($request);
    }

    private function generateStripeSignature(string $payload): string
    {
        $timestamp = time();
        $secret = config('services.stripe.webhook_secret');

        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}
