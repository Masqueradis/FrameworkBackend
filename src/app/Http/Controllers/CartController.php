<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Cart\AddToCartDTO;
use App\DTO\Cart\UpdateCartItemDTO;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends ApiController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function add(AddToCartDTO $dto): JsonResponse
    {
        $this->cartService->addItem($dto);

        return $this->respondSuccess(null, 'Item added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $dto = UpdateCartItemDTO::from([
            'cartItemId' => $cartItem->id,
            'quantity' => $request->input('quantity'),
        ]);

        $this->cartService->updateItemQuantity($dto);

        return $this->respondSuccess(null, 'Cart updated.');
    }

    public function remove(CartItem $cartItem): JsonResponse
    {
        $this->cartService->removeItem($cartItem->id);

        return $this->respondSuccess(null, 'Item removed from cart.');
    }
}
