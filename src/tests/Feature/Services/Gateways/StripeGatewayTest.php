<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Gateways;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Gateways\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

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

    protected function tearDown(): void
    {
        Mockery::close();
        ApiRequestor::setHttpClient(null);
        parent::tearDown();
    }

    #[Test]
    public function test_builds_line_items_correctly(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product', 'price' => 1500]);
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 4500,
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
    public function test_throws_exception_on_invalid_webhook_signature(): void
    {
        $payload = '{"type":"checkout.session.completed"}';
        $signature = 'invalid_signature';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Stripe signature');

        $this->gateway->verifyWebhook($payload, $signature);
    }

    #[Test]
    public function test_creates_checkout_url(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 4500,
        ]);

        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn([
                json_encode(['url' => 'https://checkout.stripe.com/pay']),
                200,
                [],
            ]);

        ApiRequestor::setHttpClient($mockClient);

        $url = $this->gateway->createCheckoutUrl($order);

        $this->assertEquals('https://checkout.stripe.com/pay', $url);
    }

    #[Test]
    public function test_verifies_valid_webhook(): void
    {
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'metadata' => ['order_id' => '10'],
                    'payment_intent' => 'pi_123',
                    'payment_status' => 'paid',
                    'amount_total' => 4500,
                ],
            ],
        ]);

        $signature = $this->generateStripeSignature($payload);

        $dto = $this->gateway->verifyWebhook($payload, $signature);

        $this->assertEquals(10, $dto->orderId);
        $this->assertEquals('pi_123', $dto->transactionId);
        $this->assertTrue($dto->isSuccess());
    }

    #[Test]
    public function test_ignores_other_webhook_events(): void
    {
        $payload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ]);

        $signature = $this->generateStripeSignature($payload);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ignored event type');

        $this->gateway->verifyWebhook($payload, $signature);
    }

    private function generateStripeSignature(string $payload): string
    {
        $timestamp = time();
        $secret = config('services.stripe.webhook_secret');

        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }

    #[Test]
    public function test_throws_exception_if_payment_not_completed(): void
    {
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['metadata' => ['order_id' => '10'], 'payment_status' => 'unpaid']],
        ]);
        $signature = $this->generateStripeSignature($payload);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment not completed yet');
        $this->gateway->verifyWebhook($payload, $signature);
    }

    #[Test]
    public function test_verifies_failed_webhook(): void
    {
        $payload = json_encode([
            'type' => 'checkout.session.expired',
            'data' => ['object' => ['metadata' => ['order_id' => '10'], 'payment_intent' => 'pi_failed']],
        ]);
        $signature = $this->generateStripeSignature($payload);

        $dto = $this->gateway->verifyWebhook($payload, $signature);
        $this->assertEquals(PaymentStatus::Failed, $dto->status);
    }

    #[Test]
    public function test_fails_if_amount_mismatch(): void
    {
        $order = Order::factory()->create(['total_amount_cents' => 5000]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'metadata' => ['order_id' => (string) $order->id],
                    'payment_status' => 'paid',
                    'amount_total' => 1000,
                    'id' => 'cs_123'
                ]
            ],
        ]);
        $signature = $this->generateStripeSignature($payload);

        $dto = $this->gateway->verifyWebhook($payload, $signature);

        $this->assertEquals(PaymentStatus::Failed, $dto->status);
    }
}
