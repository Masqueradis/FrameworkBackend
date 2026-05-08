<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Checkout\CheckoutDTO;
use App\Exceptions\EmptyCartException;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Services\CheckoutService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends ApiController
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly CartRepositoryInterface $cartRepository,
    ) {}

    public function index(): View
    {
        $cart = $this->cartRepository->findOrCreate(auth()->id(), session()->getId());

        return view('checkout.index', compact('cart'));
    }

    public function store(CheckoutDTO $data, Request $request): RedirectResponse
    {
        $cart = $this->cartRepository->findOrCreate(auth()->id(), session()->getId());

        try {
            $order = $this->checkoutService->process($data, $cart);

            $token = $request->input('payment_token', 'tok_visa');
            $this->checkoutService->processPayment($order, $token, $data->paymentProvider);

            return redirect()
                ->route('checkout.result')
                ->with([
                    'status' => 'success',
                    'message' => 'Payment successful',
                    'redirect_url' => '/catalog'
                ]);
        } catch (EmptyCartException $exception) {
            return redirect()->back()->with('error', 'Your cart is empty.');
        } catch (Exception $exception) {
            return redirect()
                ->back()
                ->with([
                    'status' => 'error',
                    'message' => 'Error while processing your payment:' . $exception->getMessage(),
                    'redirect_url' => '/cart'
                ]);
        }
    }

    public function result(): View|RedirectResponse
    {
        if(!session()->has('status')) {
            return redirect('/cart');
        }

        return view('checkout.result');
    }
}
