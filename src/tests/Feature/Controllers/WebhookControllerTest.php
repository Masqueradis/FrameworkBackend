<?php

namespace Tests\Feature\Controllers;

use App\DTO\Checkout\PaymentWebhookDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Gateways\PaddleGateway;
use App\Services\Gateways\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_handles_successful_webhook_and_updates_status(): void
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

        $dto = new PaymentWebhookDTO($order->id, 'txn_123', PaymentProvider::Stripe, PaymentStatus::Success);

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('verifyWebhook')->andReturn($dto);
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->postJson('api/v1/webhooks/stripe', []);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Completed->value,
        ]);
    }

    #[Test]
    public function test_webhook_is_idempotent_for_already_completed_orders(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Completed,
            'total_amount_cents' => 4500,
        ]);

        $dto = new PaymentWebhookDTO($order->id, 'txn_123', PaymentProvider::Stripe, PaymentStatus::Success);

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('verifyWebhook')->andReturn($dto);
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->postJson('api/v1/webhooks/stripe', []);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Completed->value,
        ]);
    }

    #[Test]
    public function test_returns400_on_signature_verification_exception(): void
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
    public function test_returns200_on_ignored_event_exception(): void
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

    #[Test]
    public function test_handles_failed_payment_webhook(): void
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

        $dto = new PaymentWebhookDTO($order->id, 'txn_123', PaymentProvider::Stripe, PaymentStatus::Failed);

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('verifyWebhook')->andReturn($dto);
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->postJson('api/v1/webhooks/stripe', []);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
    }

    #[Test]
    public function test_rethrows_generic_exceptions(): void
    {
        $this->withoutExceptionHandling();

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('verifyWebhook')
            ->once()
            ->andThrow(new \Exception('Fatal Database Error', Response::HTTP_INTERNAL_SERVER_ERROR));

        $this->app->instance(StripeGateway::class, $mockGateway);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Fatal Database Error');

        $this->postJson('api/v1/webhooks/stripe', []);
    }

    #[Test]
    public function test_handles_paddle_webhook_successfully(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending->value,
        ]);

        $dto = new PaymentWebhookDTO(
            $order->id,
            'txn_paddle_123',
            PaymentProvider::Paddle,
            PaymentStatus::Success
        );

        $mockGateway = Mockery::mock(PaddleGateway::class);
        $mockGateway->shouldReceive('verifyWebhook')
            ->once()
            ->andReturn($dto);
        $this->app->instance(PaddleGateway::class, $mockGateway);

        $response = $this->postJson('api/v1/webhooks/paddle', [], [
            'Paddle-Signature' => 'fake_paddle_signature_string',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['status' => 'success']);

        $this->assertEquals(OrderStatus::Completed, $order->fresh()->status);
    }
}
