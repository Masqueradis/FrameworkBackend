<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testAddsItemToCartAndRedirectsBack(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['stock' => 10, 'price' => 200]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Item added to cart.');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function testFailsToAddItemIfStockIsNotEnough(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['stock' => 1]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertSessionHasErrors(['quantity']);
    }

    #[Test]
    public function testRemovesItemFromCart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = Cart::create([
            'user_id' => $user->id,
            'session_id' => 'test-session-123'
        ]);
        $product = Product::factory()->create();
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        $response = $this->delete(route('cart.remove', $item->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Item removed from cart.');
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }
}
