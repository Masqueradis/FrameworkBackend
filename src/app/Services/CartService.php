<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Cart\AddToCartDTO;
use App\DTO\Cart\UpdateCartItemDTO;
use App\Models\Cart;
use App\Models\Product;
use App\Models\CartItem;
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

    /**
     * @throws \Throwable
     */
    public function addItem(AddToCartDTO $data): void
    {
        $product = Product::find($data->productId);

        if (!$product) {
            throw ValidationException::withMessages(['product' => 'Product no longer exists.']);
        }
        $cart = $this->getCart();
        $existingItem = $this->cartRepository->findItemByProductId($cart, $product->id);
        $currentQuantity = $existingItem ? $existingItem->quantity : 0;

        $newQuantity = $currentQuantity + $data->quantity;

        if ($product->stock < $newQuantity) {
            throw ValidationException::withMessages(['quantity' => 'Not enough stock available.']);
        }

        $price = new Money((int) $product->price);

        $this->cartRepository->addOrUpdateItem($cart, $product->id, $newQuantity, $price);
    }

    public function updateItemQuantity(UpdateCartItemDTO $data): void
    {
        $cart = $this->getCart();
        $item = $this->cartRepository->findItemById($cart, $data->cartItemId);
        $product = $item->product;

        if (!$product) {
            throw ValidationException::withMessages(['product' => 'Product no longer exists.']);
        }

        if ($product->stock < $data->quantity) {
            throw ValidationException::withMessages(['quantity' => 'Not enough stock available.']);
        }

        $this->cartRepository->addOrUpdateItem($cart, (int) $product->id, $data->quantity, $item->price);
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
