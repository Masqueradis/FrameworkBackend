<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\OrderCreated;
use App\Events\PaymentCompleted;
use App\Models\Order;
use App\Models\Payment;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OrderEventsTest extends TestCase
{
    #[Test]
    public function testInitializesOrderCreatedEventWithOrderModel(): void
    {
        $order = Order::make(['id' => 1, 'total' => 1000]);
        $event = new OrderCreated($order);
        $this->assertSame($order, $event->order);
    }

    #[Test]
    public function testInitializesPaymentCompletedEventWithPaymentModel(): void
    {
        $payment = Payment::make(['id' => 1, 'amount' => 1000]);
        $event = new PaymentCompleted($payment);
        $this->assertSame($payment, $event->payment);
    }
}
