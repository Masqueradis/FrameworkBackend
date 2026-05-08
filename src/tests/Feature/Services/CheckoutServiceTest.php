<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\Checkout\CheckoutDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\EmptyCartException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\CheckoutService;
use App\Services\Gateways\Contracts\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testThrowsExceptionIfCartIsEmpty(): void
    {
        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $paymentRepo = Mockery::mock(PaymentGatewayInterface::class);

        $service = new CheckoutService($cartRepo, $orderRepo, $paymentRepo);

        $cart = new Cart();
        $cart->setRelation('items', collect([]));

        $dto = new CheckoutDTO('Jonh', 'john@ex.com', null, '123 st', 'cash');

        $this->expectException(EmptyCartException::class);

        $service->process($dto, $cart);
    }

    #[Test]
    public function testProcessesCheckoutSuccessfully(): void
    {
        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $paymentRepo = Mockery::mock(PaymentGatewayInterface::class);

        $service = new CheckoutService($cartRepo, $orderRepo, $paymentRepo);

        $cart = new Cart(['user_id' => 99]);
        $cart->id = 1;

        $item = new CartItem(['price' => 100, 'quantity' => 2]);
        $cart->setRelation('items', collect([$item]));

        $dto = new CheckoutDTO('John', 'john@ex.com', null, '123 st', 'cash');
        $expectedOrder = new Order(['id' => 10]);

        $orderRepo->shouldReceive('createWithItems')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['total_amount_cents'] === 200 && $data['customer_name'] === 'John';
            }), $cart->items)
            ->andReturn($expectedOrder);

        $cartRepo -> shouldReceive('clearCart')
            ->once()
            ->with($cart->id);

        $order = $service->process($dto, $cart);

        $this->assertEquals($expectedOrder, $order);
    }

    #[Test]
    public function testProcessesPaymentSuccessfully(): void
    {
        $service = $this->app->make(CheckoutService::class);
        $order = Order::create([
            'customer_name' => 'John',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123 st',
            'total_amount_cents' => 1000,
            'status' => OrderStatus::Pending
        ]);

        $service->processPayment($order, 'tok_4242', 'fake_provider');

        $order->refresh();

        $this->assertEquals(OrderStatus::Processing, $order->status);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => PaymentStatus::Paid->value,
            'amount_cents' => 1000,
            'provider' => 'fake_provider',
        ]);
    }

    public function testFailsPaymentProperly(): void
    {
        $service = $this->app->make(CheckoutService::class);
        $order = Order::create([
            'customer_name' => 'John',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123 st',
            'total_amount_cents' => 1000,
            'status' => OrderStatus::Pending
        ]);

        $service->processPayment($order, 'tok_error', 'fake_provider');

        $order->refresh();

        $this->assertEquals(OrderStatus::Cancelled, $order->status);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => PaymentStatus::Failed->value,
        ]);
    }
}
