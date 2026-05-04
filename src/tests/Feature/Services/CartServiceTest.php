<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\Cart\AddToCartDTO;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\ValueObjects\Cart\CartQuantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

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
    public function testAddsItemToCartSuccessfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'price' => 1000,
            'stock' => 50
        ]);

        $dto = new AddToCartDto($product->id, 2);

        $this->cartService->addItem($dto);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 1000,
        ]);
    }

    #[Test]
    public function testThrowsExceptionIfInsufficientStock(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['stock' => 1]);
        $dto = new AddToCartDto($product->id, 5);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Not enough stock available.');
        $this->cartService->addItem($dto);
    }

    #[Test]
    public function testCalculateCartTotalCorrectly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product1 = Product::factory()->create(['price' => 1000, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 2000, 'stock' => 10]);

        $this->cartService->addItem(new AddToCartDto($product1->id, 2));
        $this->cartService->addItem(new AddToCartDto($product2->id, 1));

        $cart = $this->cartService->getCart();
        $total = $this->cartService->calculateTotal($cart);

        $this->assertEquals(4000, $total->getCents());
    }
}
