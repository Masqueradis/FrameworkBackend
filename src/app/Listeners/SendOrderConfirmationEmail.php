<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
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

        /** @var OrderStatus $status */
        $status = $event->order->status;

        if ($status === OrderStatus::Pending) {
            return;
        }

        Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
    }
}
