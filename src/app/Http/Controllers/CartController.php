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
use Illuminate\View\View;

class CartController extends ApiController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(): View
    {
        $cart = $this->cartService->getCart();

        $cart->load('items.product');

        $total = $this->cartService->calculateTotal($cart);

        return view('cart.index', compact('cart', 'total'));
    }

    public function add(AddToCartDTO $dto): RedirectResponse
    {
        $this->cartService->addItem($dto);

        return back()->with('success', 'Item added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $dto = UpdateCartItemDTO::from([
            'cartItemId' => $cartItem->id,
            'quantity' => $request->input('quantity'),
        ]);

        $this->cartService->updateItemQuantity($dto);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(CartItem $cartItem): RedirectResponse
    {
        $this->cartService->removeItem($cartItem->id);

        return back()->with('success', 'Item removed from cart.');
    }
}
