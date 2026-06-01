<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\Checkout\CheckoutDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Exceptions\EmptyCartException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checkoutService = $this->app->make(OrderService::class);
    }

    #[Test]
    public function processThrowsExceptionIfCartIsEmpty(): void
    {
        $cart = Cart::create();
        $dto = new CheckoutDTO(
            'John',
            'test@example.com',
            '123',
            'Address',
            'stripe'
        );

        $this->expectException(EmptyCartException::class);
        $this->checkoutService->process($dto, $cart);
    }

    #[Test]
    public function processCreatesOrderAndCalculatesTotal(): void
    {
        $cart = Cart::create();
        $product = Product::factory()->create(['price' => 1000]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $dto = new CheckoutDTO(
            'John',
            'test@example.com',
            '123',
            'Address',
            'stripe'
        );

        $order = $this->checkoutService->process($dto, $cart);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(2000, $order->total_amount_cents->getCents());
        $this->assertEquals(OrderStatus::Pending, $order->status);
        $this->assertDatabaseHas('orders', ['customer_email' => 'test@example.com']);
    }

    #[Test]
    public function handleWebhookSuccessUpdatesStatus(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = Product::factory()->create(['price' => 10, 'stock' => 8]);
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'total_amount_cents' => $product->price,
            'status' => OrderStatus::Pending,
            'total_amount_cents' => 1000
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price_cents' => 1000,
            'product_name' => $product->name,
        ]);

        $this->checkoutService->handleWebhook($order, true, 'txn_123', 'stripe');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'transaction_id' => 'txn_123',
            'status' => PaymentStatus::Paid
        ]);

        $this->assertEquals(OrderStatus::Completed, $order->fresh()->status);
        $this->assertEquals(8, $product->fresh()->stock);
    }

    #[Test]
    public function handleWebhookFailureCancelsOrder(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = Product::factory()->create(['price' => 10, 'stock' => 8]);
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'total_amount_cents' => $product->price,
            'status' => OrderStatus::Pending
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price_cents' => 1000,
            'product_name' => $product->name,
        ]);

        $this->checkoutService->handleWebhook($order, false, 'txn_fail', 'stripe');

        $this->assertDatabaseHas('payments', [
            'status' => PaymentStatus::Failed,
        ]);

        $this->assertEquals(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock);
    }

    #[Test]
    public function testThrowsExceptionIfNotEnoughStock(): void
    {
        $cart = Cart::create();
        $product = Product::factory()->create(['price' => 1000, 'stock' => 1]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
            'product_name' => $product->name,
        ]);

        $dto = new CheckoutDTO(
            'John',
            'test@example.com',
            '123',
            'Address',
            'stripe'
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Not enough stock for {$product->name}.");
        $this->checkoutService->process($dto, $cart);
    }

    #[Test]
    public function testDispatchesOrderCreatedEventOnSuccessfulCheckout(): void
    {
        Event::fake();

        $cart = Cart::create();
        $product = Product::factory()->create(['price' => 1000]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        $dto = new CheckoutDTO(
            'John',
            'test@example.com',
            '123',
            'Address',
            'stripe',
        );

        $order = $this->checkoutService->process($dto, $cart);

        Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }
}
