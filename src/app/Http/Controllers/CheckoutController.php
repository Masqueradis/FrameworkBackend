<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Checkout\CheckoutDTO;
use App\Exceptions\EmptyCartException;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Gateways\GatewayFactory;
use App\Services\Gateways\StripeGateway;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

class CheckoutController extends ApiController
{
    public function __construct(
        private readonly OrderService $checkoutService,
        private readonly CartRepositoryInterface $cartRepository,
    ) {}

    public function index(CartService $cartService): View
    {
        $userId = auth()->id() ? (int) auth()->id() : null;
        $cart = $this->cartRepository->findOrCreate($userId, session()->getId());

        $total = $cartService->calculateTotal($cart);

        return view('checkout.index', compact('cart', 'total'));
    }

    #[OA\Post(
        path: '/checkout',
        description: 'Process the active session cart and generate a payment URL for the chosen gateway. Requires active session with items in the cart.',
        summary: 'Create checkout session',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customerName', 'customerEmail', 'shippingAddress', 'paymentProvider'],
                properties: [
                    new OA\Property(property: 'customerName', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'customerEmail', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'customerPhone', type: 'string', example: '+1234567890', nullable: true),
                    new OA\Property(property: 'shippingAddress', type: 'string', example: '123 Main St, New York, NY'),
                    new OA\Property(property: 'paymentProvider', type: 'string', example: 'stripe'),
                ]
            )
        ),
        tags: ['Checkout'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Checkout URL generated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'provider', type: 'string', example: 'stripe'),
                        new OA\Property(property: 'action', type: 'string', example: 'https://checkout.stripe.com/pay/...'),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Cart is empty validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Your cart is empty.'),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Server or Payment Gateway error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Gateway timeout'),
                    ]
                )
            ),
        ]
    )]
    public function store(CheckoutDTO $data): JsonResponse
    {
        $userId = auth()->id() ? (int) auth()->id() : null;
        $cart = $this->cartRepository->findOrCreate($userId, session()->getId());

        $lock = Cache::lock('checkout_cart_' . $cart->id, 10);

        if (!$lock->get()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Checkout is already in progress. Please wait.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $order = $this->checkoutService->process($data, $cart);

            $this->cartRepository->clearCart($cart->id);

            $gateway = GatewayFactory::make($data->paymentProvider);
            $result = $gateway->createCheckoutUrl($order);

            return response()->json([
                'status' => 'success',
                'provider' => $data->paymentProvider->value,
                'action' => $result,
            ]);
        } catch (EmptyCartException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your cart is empty.',
            ], Response::HTTP_BAD_REQUEST);
        } finally {
            $lock->release();
        }
    }

    public function result(Request $request): View|RedirectResponse
    {
        $status = $request->query('status', 'pending');

        return view('checkout.result', ['status' => $status]);
    }
}
