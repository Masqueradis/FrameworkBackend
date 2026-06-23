<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelAbandonedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cancel-abandoned-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancelled old unpaid  orders and returns them to catalog';

    /**
     * Execute the console command.
     */
    public function handle(OrderRepositoryInterface $orderRepository): void
    {
        $abandonedOrders = Order::where('status', OrderStatus::Pending->value)
            ->where('created_at', '<', now()->subMinutes(30))
            ->get();

        foreach ($abandonedOrders as $order) {

            DB::transaction(function () use ($order, $orderRepository) {

                $affected = Order::where('id', $order->id)
                    ->where('status', OrderStatus::Pending->value)
                    ->update(['status' => OrderStatus::Cancelled->value]);

                if ($affected > 0) {
                    $orderRepository->restoreStock($order);
                    $this->info("Order #{$order->id} has been cancelled. Products are returned to store.");
                }
            });
        }
    }
}
