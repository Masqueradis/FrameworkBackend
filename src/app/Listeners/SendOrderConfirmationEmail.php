<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\OrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public string $connection = 'rabbitmq';
    public string $queue = 'emails_queue';

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        if ($order->status === \App\Enums\OrderStatus::Pending) {
            return;
        }

        Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
    }
}
