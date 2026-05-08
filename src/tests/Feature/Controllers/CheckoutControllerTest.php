<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testShowsCheckoutForm(): void
    {
        $response = $this->get(route('checkout.index'));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('checkout.index');
    }

    #[Test]
    public function testProcessesCheckoutAndRedirectsToSuccess(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 500]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 500,
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+0123456789',
            'shipping_address' => '123 st',
            'payment_provider' => 'stripe',
            'payment_token' => 'tok_visa'
        ]);

        $response->assertRedirect(route('checkout.result'));
        $response->assertSessionHas('status', 'success');

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'test@example.com',
        ]);

        $this->assertDatabaseEmpty('cart_items');
    }
}
