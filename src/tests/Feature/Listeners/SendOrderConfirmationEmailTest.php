<?php

namespace Tests\Feature\Listeners;

use App\Events\OrderCreated;
use App\Listeners\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SendOrderConfirmationEmailTest extends TestCase
{
    #[Test]
    public function testIsAttachedToOrderCreatedEvent(): void
    {
        Event::fake();

        Event::assertListening(
            OrderCreated::class,
            SendOrderConfirmationEmail::class
        );
    }

    #[Test]
    public function testSendsEmailWhenHandled(): void
    {
        Mail::fake();

        $order = new Order([
            'id' => 123,
            'customer_email' => 'test@example.com',
            'customer_name' => 'John Doe',
        ]);

        $event = new OrderCreated($order);
        $listener = new SendOrderConfirmationEmail();

        $listener->handle($event);

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order) {
            return $mail->hasTo($order->customer_email)
                && $mail->order->id === $order->id;
        });
    }
}
