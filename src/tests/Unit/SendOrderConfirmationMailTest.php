<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Listeners\SendOrderConfirmationEmail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendOrderConfirmationMailTest extends TestCase
{
    public function test_does_not_send_email_if_order_is_pending(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 4500,
        ]);

        $event = new OrderCreated($order);
        $listener = new SendOrderConfirmationEmail;

        $listener->handle($event);

        Mail::assertNothingSent();
    }
}
