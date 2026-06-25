<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\OrderCreated;
use App\Events\PaymentCompleted;
use App\Models\Order;
use App\Models\Payment;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderEventsTest extends TestCase
{
    #[Test]
    public function test_initializes_order_created_event_with_order_model(): void
    {
        $order = Order::make(['id' => 1, 'total' => 1000]);
        $event = new OrderCreated($order);
        $this->assertSame($order, $event->order);
    }

    #[Test]
    public function test_initializes_payment_completed_event_with_payment_model(): void
    {
        $payment = Payment::make(['id' => 1, 'amount' => 1000]);
        $event = new PaymentCompleted($payment);
        $this->assertSame($payment, $event->payment);
    }
}
