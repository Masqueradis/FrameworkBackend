<?php

namespace Tests\Feature\Controllers;

use App\DTO\Checkout\PaymentWebhookDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Gateways\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function testHandlesSuccessfulWebhook(): void
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
        $dto = new PaymentWebhookDTO($order->id, 'txn_123', 'stripe', 'success');

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('verifyWebhook')->andReturn($dto);
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->postJson('api/v1/webhooks/stripe', []);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['status' => 'success']);
    }

    #[Test]
    public function testReturns400OnSignatureVerificationException(): void
    {
        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('verifyWebhook')
            ->once()
            ->andThrow(new \Exception('Invalid signature', Response::HTTP_BAD_REQUEST));
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->postJson('api/v1/webhooks/stripe', []);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson(['message' => 'Invalid signature']);
    }

    #[Test]
    public function testReturns200OnIgnoredEventException(): void
    {
        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('verifyWebhook')
            ->once()
            ->andThrow(new \Exception('Ignored event type', Response::HTTP_OK));
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->postJson('api/v1/webhooks/stripe', []);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['message' => 'Ignored event type']);
    }
}
