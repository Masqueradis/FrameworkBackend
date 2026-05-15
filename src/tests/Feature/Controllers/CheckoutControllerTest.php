<?php

namespace Tests\Feature\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Gateways\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function testDisplaysCheckoutPage(): void
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
    public function testProcessesCheckoutAndReturnsGatewayUrl(): void
    {
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
            'payment_provider' => 'stripe'
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'status' => 'success',
            'provider' => 'stripe',
            'action' => 'https://fake-stripe.com/pay'
        ]);
    }

    #[Test]
    public function testReturns400ForEmptyCart(): void
    {
        $user = User::factory()->create();
        Cart::create(['user_id' => $user->id, 'session_id' => '123']);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'customer_name' => 'John',
            'customer_email' => 'test@example.com',
            'customer_phone' => '123',
            'shipping_address' => '123',
            'payment_provider' => 'stripe'
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Your cart is empty.'
        ]);
    }

    #[Test]
    public function testReturns500OnGenericException(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'session_id' => '123']);
        $product = Product::factory()->create(['price' => 1000, 'stock' => 10]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1000
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
            'payment_provider' => 'stripe'
        ]);

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Gateway timeout'
        ]);
    }

    #[Test]
    public function testDisplaysResultAndClearsCart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'session_id' => '123']);
        $product = Product::factory()->create();
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1000
        ]);

        $response = $this->actingAs($user)->get('/checkout/result');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('checkout.result');
        $response->assertViewHas('status', 'success');

        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }
}
