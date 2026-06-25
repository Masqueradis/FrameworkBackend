<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Checkout\CheckoutDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Exceptions\EmptyCartException;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\CartService;
use App\Services\Gateways\GatewayFactory;
use App\Services\OrderService;
use App\ValueObjects\Cart\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

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

        $lock = Cache::lock('checkout_cart_'.$cart->id, 10);

        if (! $lock->get()) {
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

    public function cancel(Order $order, OrderRepositoryInterface $orderRepository): RedirectResponse
    {
        /** @var mixed $rawStatus */
        $rawStatus = $order->status;
        $statusStr = $rawStatus instanceof \BackedEnum ? $rawStatus->value : (string) $rawStatus;

        if ((int) $order->user_id === (int) auth()->id() && $statusStr === OrderStatus::Pending->value) {

            $cart = $this->cartRepository->findOrCreate((int) auth()->id(), session()->getId());

            $order->load('items');
            foreach ($order->items as $item) {
                /** @var mixed $rawPrice */
                $rawPrice = $item->price_cents;

                $price = $rawPrice instanceof Money
                    ? $rawPrice
                    : new Money((int) $rawPrice);

                $this->cartRepository->addOrUpdateItem(
                    $cart,
                    (int) $item->product_id,
                    $item->quantity,
                    $price
                );
            }

            $order->update(['status' => OrderStatus::Cancelled->value]);
            $orderRepository->restoreStock($order);
        }

        return redirect()->route('checkout.index')->with('error_alert', 'Payment was cancelled. Your cart has been restored.');
    }

    public function retry(Order $order): JsonResponse|RedirectResponse
    {
        /** @var mixed $rawStatus */
        $rawStatus = $order->status;
        $statusStr = $rawStatus instanceof \BackedEnum ? $rawStatus->value : (string) $rawStatus;

        if ((int) $order->user_id !== (int) auth()->id() || $statusStr !== OrderStatus::Pending->value) {
            return redirect()->route('catalog.index')->with('error_alert', 'This order cannot be paid.');
        }

        try {
            $lastProvider = $order->payments()->latest()->value('provider');
            $providerName = $lastProvider ?? request('provider', 'stripe');
            $provider = PaymentProvider::from($providerName);

            $gateway = GatewayFactory::make($provider);
            $url = $gateway->createCheckoutUrl($order);

            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'provider' => $provider->value,
                    'action' => $url,
                ]);
            }

            return redirect()->away($url);

        } catch (\Exception $exception) {
            return redirect()->back()->with('error_alert', 'Gateway error: '.$exception->getMessage());
        }
    }

    public function decline(Order $order, OrderRepositoryInterface $orderRepository): RedirectResponse
    {
        /** @var mixed $rawStatus */
        $rawStatus = $order->status;
        $statusStr = $rawStatus instanceof \BackedEnum ? $rawStatus->value : (string) $rawStatus;

        if ((int) $order->user_id !== (int) auth()->id() || $statusStr !== OrderStatus::Pending->value) {
            return redirect()->back()->with('error_alert', 'This order cannot be cancelled.');
        }

        $order->update(['status' => OrderStatus::Cancelled->value]);

        $orderRepository->restoreStock($order);

        return redirect()->back()->with('status', 'Order #'.$order->id.' was successfully cancelled.');
    }
}
