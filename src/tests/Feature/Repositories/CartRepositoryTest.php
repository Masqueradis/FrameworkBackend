<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\ValueObjects\Cart\Money;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CartRepositoryInterface $cartRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartRepository = $this->app->make(CartRepositoryInterface::class);
    }

    #[Test]
    public function testFindsOrCreateCartForGuestBySessionId(): void
    {
        Cart::query()->delete();
        $sessionId = 'test-session-123';

        $cart1 = $this->cartRepository->findOrCreate(null, $sessionId);
        $this->assertInstanceOf(Cart::class, $cart1);
        $this->assertEquals($sessionId, $cart1->session_id);
        $this->assertNull($cart1->user_id);

        $cart2 = $this->cartRepository->findOrCreate(null, $sessionId);
        $this->assertEquals($cart1->id, $cart2->id);
    }

    #[Test]
    public function testFindsOrCreateCartForAuthUser(): void
    {
        $user = User::factory()->create();

        $cart1 = $this->cartRepository->findOrCreate($user->id, null);
        $this->assertInstanceOf(Cart::class, $cart1);
        $this->assertEquals($user->id, $cart1->user_id);

        $cart2 = $this->cartRepository->findOrCreate($user->id, null);
        $this->assertEquals($cart1->id, $cart2->id);
    }

    #[Test]
    public function testAddsItemToCart(): void
    {
        $cart = Cart::create(['session_id' => 'test-session-123']);
        $product = Product::factory()->create();
        $price = new Money(1500);
        $item = $this->cartRepository->addOrUpdateItem($cart, $product->id, 2, $price);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 1500,
        ]);

        $this->cartRepository->addOrUpdateItem($cart, $product->id, 5, $price);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    #[Test]
    public function testCartBelongsToUser(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id, 'session_id' => 'test-session-123']);

        $relation = $cart->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals($user->id, $cart->user->id);
    }

    #[Test]
    public function testCartItemBelongsToUser(): void
    {
        $cart = Cart::create(['session_id' => 'test-session-123']);
        $product = Product::factory()->create(['price' => 500]);
        $cartItem = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2, 'price' => $product->price]);

        $relation = $cartItem->cart();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals($cart->id, $cartItem->cart->id);
    }
}
