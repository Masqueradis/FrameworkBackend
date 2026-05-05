<?php

namespace Tests\Feature\Repositories;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testCreateOrderWithItemsSuccessfully(): void
    {
        $repository = $this->app->make(OrderRepositoryInterface::class);
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100]);

        $orderData = [
            'user_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123 Main Street',
            'total_amount_cents' => 200,
        ];

        $cartItem = new CartItem([
            'cart_id' => 1,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100,
        ]);

        $cartItem->setRelation('product', $product);

        $cartItems = collect([$cartItem]);

        $order = $repository->createWithItems($orderData, $cartItems);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_name' => 'John Doe',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price_cents' => 100,
        ]);
    }
}
