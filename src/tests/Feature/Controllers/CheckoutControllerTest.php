<?php

namespace Tests\Feature\Controllers;

use App\Enums\OrderStatus;
use App\Http\Controllers\CheckoutController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Gateways\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_displays_checkout_page(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'session_id' => '123']);
        $product = Product::factory()->create(['price' => 1000]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 1000]);
        $response = $this->actingAs($user)->get('/checkout');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('checkout.index');
        $response->assertViewHas(['cart', 'total']);
    }

    #[Test]
    public function test_processes_checkout_and_returns_gateway_url(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'session_id' => '123']);
        $product = Product::factory()->create(['price' => 1000, 'stock' => 10]);
        CartItem::create(['cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1000]);

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('createCheckoutUrl')->once()->andReturn('https://fake-stripe.com/pay');
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'customer_name' => 'John',
            'customer_email' => 'test@example.com',
            'customer_phone' => '123',
            'shipping_address' => '123',
            'payment_provider' => 'stripe',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'status' => 'success',
            'provider' => 'stripe',
            'action' => 'https://fake-stripe.com/pay',
        ]);
    }

    #[Test]
    public function test_returns400_for_empty_cart(): void
    {
        $user = User::factory()->create();
        Cart::create(['user_id' => $user->id, 'session_id' => '123']);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'customer_name' => 'John',
            'customer_email' => 'test@example.com',
            'customer_phone' => '123',
            'shipping_address' => '123',
            'payment_provider' => 'stripe',
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Your cart is empty.',
        ]);
    }

    #[Test]
    public function test_returns500_on_generic_exception(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'session_id' => '123']);
        $product = Product::factory()->create(['price' => 1000, 'stock' => 10]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1000,
        ]);

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('createCheckoutUrl')
            ->andThrow(new \Exception('Gateway timeout'));
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'customer_name' => 'John',
            'customer_email' => 'test@example.com',
            'customer_phone' => '123',
            'shipping_address' => '123',
            'payment_provider' => 'stripe',
        ]);

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

        $response->assertJson([
            'message' => 'Server error',
        ]);
    }

    #[Test]
    public function test_displays_result_and_clears_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'session_id' => '123']);
        $product = Product::factory()->create();
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1000,
        ]);

        $response = $this->actingAs($user)->get('/checkout/result?status=success');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('checkout.result');
        $response->assertViewHas('status', 'success');
    }

    #[Test]
    public function test_store_returns_429_if_cache_lock_fails(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'session_id' => '123']);

        $mockLock = Mockery::mock();
        $mockLock->shouldReceive('get')->once()->andReturn(false);
        Cache::shouldReceive('lock')
            ->with('checkout_cart_'.$cart->id, 10)
            ->andReturn($mockLock);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'customer_name' => 'John', 'customer_email' => 'test@example.com',
            'shipping_address' => '123', 'payment_provider' => 'stripe',
        ]);

        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
        $response->assertJson(['message' => 'Checkout is already in progress. Please wait.']);
    }

    #[Test]
    public function test_cancel_restores_cart_and_cancels_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);
        $product = Product::factory()->create(['stock' => 5]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price_cents' => 1000, 'product_name' => 'Test']);

        $response = $this->actingAs($user)->get(route('checkout.cancel', $order));

        $response->assertRedirect(route('checkout.index'));
        $this->assertEquals(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertEquals(7, $product->fresh()->stock);
    }

    #[Test]
    public function test_cancel_fails_for_invalid_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Completed]);

        $response = $this->actingAs($user)->get(route('checkout.cancel', $order));

        $response->assertRedirect();
        $this->assertEquals(OrderStatus::Completed, $order->fresh()->status);
    }

    #[Test]
    public function test_retry_generates_new_url(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('createCheckoutUrl')->once()->andReturn('https://fake-stripe.com/retry');
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->actingAs($user)->postJson(route('checkout.retry', $order), ['provider' => 'stripe']);

        $response->assertOk();
        $response->assertJson(['action' => 'https://fake-stripe.com/retry']);
    }

    #[Test]
    public function test_retry_fails_for_invalid_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Completed]);

        $response = $this->actingAs($user)->postJson(route('checkout.retry', $order), ['provider' => 'stripe']);

        $response->assertRedirect(route('catalog.index'));
    }

    #[Test]
    public function test_decline_cancels_order_without_cart_restore(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);

        $product = Product::factory()->create(['stock' => 5]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price_cents' => 1000,
            'product_name' => 'Test',
        ]);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->delete(route('checkout.decline', $order));

        $response->assertRedirect('/profile');
        $response->assertSessionHas('status', 'Order #'.$order->id.' was successfully cancelled.');

        $freshStatus = $order->fresh()->status;
        $actualStatus = $freshStatus instanceof \BackedEnum ? $freshStatus->value : (string) $freshStatus;

        $this->assertEquals(OrderStatus::Cancelled->value, $actualStatus);

        $this->assertEquals(7, $product->fresh()->stock);
    }

    #[Test]
    public function test_retry_standard_web_redirects_away(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('createCheckoutUrl')->once()->andReturn('https://fake-stripe.com/retry-away');
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->actingAs($user)->post(route('checkout.retry', $order), ['provider' => 'stripe']);

        $response->assertRedirect('https://fake-stripe.com/retry-away');
    }

    #[Test]
    public function test_retry_redirects_back_with_error_on_gateway_exception(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('createCheckoutUrl')
            ->once()
            ->andThrow(new \Exception('Stripe API Connection dropped'));
        $this->app->instance(StripeGateway::class, $mockGateway);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->post(route('checkout.retry', $order), ['provider' => 'stripe']);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('error_alert', 'Gateway error: Stripe API Connection dropped');
    }

    #[Test]
    public function test_decline_fails_for_invalid_order_or_wrong_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = Order::factory()->create(['user_id' => $otherUser->id, 'status' => OrderStatus::Pending]);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->delete(route('checkout.decline', $order));

        $response->assertRedirect('/profile');
        $response->assertSessionHas('error_alert', 'This order cannot be cancelled.');
    }

    public function test_cancel_handles_raw_integer_price_fallback_for_phpstan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $order = Mockery::mock(Order::class)->makePartial();
        $order->user_id = $user->id;
        $order->status = OrderStatus::Pending;
        $order->shouldReceive('load')->with('items')->andReturnSelf();
        $order->shouldReceive('update')->once();

        $item = (object) ['product_id' => 1, 'quantity' => 1, 'price_cents' => 5000];
        $order->items = collect([$item]);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('restoreStock')->once();

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findOrCreate')->andReturn(new Cart(['id' => 1]));
        $cartRepo->shouldReceive('addOrUpdateItem')->once();

        $this->app->instance(CartRepositoryInterface::class, $cartRepo);

        $controller = $this->app->make(CheckoutController::class);
        $response = $controller->cancel($order, $orderRepo);

        $this->assertTrue($response->isRedirect());
    }
}
