<?php

namespace Tests\Feature\Listeners;

use App\Events\OrderCreated;
use App\Listeners\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendOrderConfirmationEmailTest extends TestCase
{
    #[Test]
    public function test_is_attached_to_order_created_event(): void
    {
        Event::fake();

        Event::assertListening(
            OrderCreated::class,
            SendOrderConfirmationEmail::class
        );
    }

    #[Test]
    public function test_sends_email_when_handled(): void
    {
        Mail::fake();

        $order = new Order([
            'id' => 123,
            'customer_email' => 'test@example.com',
            'customer_name' => 'John Doe',
        ]);

        $event = new OrderCreated($order);
        $listener = new SendOrderConfirmationEmail;

        $listener->handle($event);

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order) {
            return $mail->hasTo($order->customer_email)
                && $mail->order->id === $order->id;
        });
    }
}
