<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PaymentWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testHandlesSuccessfulPaymentWebhook(): void
    {
        $order = Order::create([
            'customer_name' => 'John',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123 st',
            'total_amount_cents' => 1000,
            'status' => OrderStatus::Pending,
        ]);

        $payload = [
            'order_id' => $order->id,
            'transaction_id' => 'txn_12345',
            'status' => 'success',
            'signature' => 'secret_hash_123'
        ];

        $response = $this->postJson('/api/webhooks/fake-payment', $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['message' => 'Webhook processed']);

        $order->refresh();
        $this->assertEquals(OrderStatus::Processing, $order->status);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'transaction_id' => 'txn_12345',
            'status' => PaymentStatus::Paid->value,
        ]);
    }
}
