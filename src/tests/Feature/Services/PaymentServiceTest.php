<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\Payment\CheckoutDTO;
use App\Exceptions\EmptyCartException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    #[Test]
    public function testThrowsExceptionIfCartIsEmpty(): void
    {
        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);

        $service = new PaymentService($cartRepo, $orderRepo);

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

        $service = new PaymentService($cartRepo, $orderRepo);

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

}
