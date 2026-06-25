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
        $freshOrder = $event->order->fresh();

        /** @var mixed $rawStatus */
        $rawStatus = $freshOrder ? $freshOrder->status : $event->order->status;

        $status = $rawStatus instanceof \BackedEnum ? $rawStatus->value : (string) $rawStatus;

        if ($status === OrderStatus::Cancelled->value || $status === OrderStatus::Pending->value) {
            return;
        }

        $order = $freshOrder ?? $event->order;

        Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
    }
}
