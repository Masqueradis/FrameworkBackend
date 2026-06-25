<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\Cart\AddToCartDTO;
use App\DTO\Cart\UpdateCartItemDTO;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Services\CartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = $this->app->make(CartService::class);
    }

    #[Test]
    public function test_adds_item_to_cart_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'price' => 1000,
            'stock' => 50,
        ]);

        $data = new AddToCartDTO($product->id, 2);

        $this->cartService->addItem($data);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100000,
        ]);
    }

    #[Test]
    public function test_throws_exception_if_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['stock' => 1]);
        $data = new AddToCartDTO($product->id, 5);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Not enough stock available.');
        $this->cartService->addItem($data);
    }

    #[Test]
    public function test_calculate_cart_total_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product1 = Product::factory()->create(['price' => 1000, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 2000, 'stock' => 10]);

        $this->cartService->addItem(new AddToCartDTO($product1->id, 2));
        $this->cartService->addItem(new AddToCartDTO($product2->id, 1));

        $cart = $this->cartService->getCart();
        $total = $this->cartService->calculateTotal($cart);

        $this->assertEquals(400000, $total->getCents());
    }

    #[Test]
    public function test_updates_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = Cart::create([
            'user_id' => $user->id,
            'session_id' => 'test-session-123',
        ]);
        $product = Product::factory()->create(['stock' => 10, 'price' => 200]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        $response = $this->patch(route('cart.update', $item->id), [
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Cart updated.');

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 5,
        ]);
    }

    #[Test]
    public function test_update_item_quantity_throws_invalid_quantity_exception(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = Cart::create([
            'user_id' => $user->id,
            'session_id' => 'test-session-123',
        ]);
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 200,
        ]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        $product->delete();

        $data = UpdateCartItemDTO::from([
            'cartItemId' => $item->id,
            'quantity' => 2,
        ]);

        $this->expectException(ModelNotFoundException::class);

        $this->cartService->updateItemQuantity($data);
    }

    #[Test]
    public function test_updates_item_quantity_throws_exception_if_stock_exceeded(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = Cart::create([
            'user_id' => $user->id,
            'session_id' => 'test-session-123',
        ]);

        $product = Product::factory()->create(['stock' => 3, 'price' => 200]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        $data = UpdateCartItemDTO::from(['cartItemId' => $item->id, 'quantity' => 5]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Not enough stock available.');

        $this->cartService->updateItemQuantity($data);
    }

    #[Test]
    public function test_throws_exception_if_product_does_not_exist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $data = new AddToCartDTO(99999, 1);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Product no longer exists.');
        $this->cartService->addItem($data);
    }

    #[Test]
    public function test_update_item_quantity_throws_exception_if_product_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = Cart::create(['user_id' => $user->id]);

        $item = new CartItem(['id' => 1, 'cart_id' => $cart->id, 'product_id' => 99999]);

        $mockRepo = \Mockery::mock(CartRepositoryInterface::class);
        $mockRepo->shouldReceive('findOrCreate')->andReturn($cart);
        $mockRepo->shouldReceive('findItemById')->andReturn($item);

        $service = new CartService($mockRepo);

        $data = UpdateCartItemDTO::from([
            'cartItemId' => 1,
            'quantity' => 2
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Product no longer exists.');

        $service->updateItemQuantity($data);
    }
}
