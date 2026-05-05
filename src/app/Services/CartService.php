<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Cart\AddToCartDTO;
use App\DTO\Cart\UpdateCartItemDTO;
use App\Models\Cart;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\ValueObjects\Cart\Money;
use Illuminate\Validation\ValidationException;

readonly class CartService
{
    public function __construct(
        private CartRepositoryInterface $cartRepository
    ) {}

    public function getCart(): Cart
    {
        $userId = auth()->id() !== null ? (int) auth()->id() : null;

        $sessionId = session()->getId();

        return $this->cartRepository->findOrCreate($userId, $sessionId);
    }

    public function addItem(AddToCartDTO $dto): void
    {
        $product = Product::findOrFail($dto->productId);
        $cart = $this->getCart();
        $existingItem = $cart->items()->where('product_id', $product->id)->first();
        $currentQuantity = $existingItem ? $existingItem->quantity : 0;

        $newQuantity = $currentQuantity + $dto->quantity;

        if ($newQuantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Not enough stock available.'
            ]);
        }

        $price = new Money((int) $product->price);

        $this->cartRepository->addOrUpdateItem($cart, $product->id, $newQuantity, $price);
    }

    public function updateItemQuantity(UpdateCartItemDTO $dto): void
    {
        $cart = $this->getCart();
        $item = $cart->items()->findOrFail($dto->cartItemId);
        $product = $item->product;

        if(!$product) {
            throw ValidationException::withMessages([
                'quantity' => 'Product no longer exists.'
            ]);
        }

        if($dto->quantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Not enough stock available.'
            ]);
        }

        $this->cartRepository->addOrUpdateItem($cart, $product->id, $dto->quantity, $item->price);
    }

    public function removeItem(int $cartItemId): void
    {
        $cart = $this->getCart();
        $item = $cart->items()->findOrFail($cartItemId);

        $this->cartRepository->removeItem($item->id);
    }

    public function calculateTotal(Cart $cart): Money
    {
        $totalCents = 0;

        foreach ($cart->items as $item) {
            $totalCents += $item->price->getCents() * $item->quantity;
        }

        return new Money($totalCents);
    }
}
