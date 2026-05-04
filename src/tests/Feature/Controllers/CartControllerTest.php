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

        $response = $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Item added to cart.'
        ]);

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

        $response = $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['quantity']);
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

        $response = $this->deleteJson(route('cart.remove', $item->id));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Item removed from cart.'
        ]);
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }
}
