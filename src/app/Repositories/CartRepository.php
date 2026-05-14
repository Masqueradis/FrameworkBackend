<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\ValueObjects\Cart\Money;

class CartRepository implements CartRepositoryInterface
{
    public function findOrCreate(?int $userId, ?string $sessionId): Cart
    {
        if ($userId !== null) {
            return Cart::firstOrCreate(['user_id' => $userId]);
        }

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    public function addOrUpdateItem(Cart $cart, int $productId, int $quantity, Money $price): CartItem
    {
        $item = $this->findItemByProductId($cart, $productId);

        if($item) {
            $item->update([
                'quantity' => $quantity,
                'price' => $price,
            ]);
            return $item;
        }

        return $cart->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price,
        ]);
    }

    public function removeItem(int $cartItemId): void
    {
        CartItem::destroy($cartItemId);
    }

    public function clearCart(int $cartId): void
    {
        Cart::find($cartId)?->items()->delete();
    }

    public function findItemByProductId(Cart $cart, int $productId): ?CartItem
    {
        return $cart->items()->where('product_id', $productId)->first();
    }

    public function findItemById(Cart $cart, int $cartItemId): CartItem
    {
        return $cart->items()->where('id', $cartItemId)->firstOrFail();
    }
}
