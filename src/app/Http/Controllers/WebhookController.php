<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentProvider;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Services\Gateways\StripeGateway;
use App\Services\OrderService;
use App\Services\Gateways\GatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

class WebhookController extends ApiController
{
    #[OA\Post(
        path: '/api/v1/webhooks/{provider}',
        description: 'Process incoming webhooks from payment providers like Stripe or Paddle. Validates the signature and updates order status.',
        summary: 'Handle payment webhook',
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                description: 'Payment provider name (e.g., stripe, paddle)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Webhook processed successfully or ignored (e.g., irrelevant event type)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Ignored event type', nullable: true),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Invalid webhook signature',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid signature'),
                    ]
                )
            ),
        ]
    )]
    public function handle(Request $request, string $provider, OrderService $checkoutService): JsonResponse
    {
        try {
            $paymentProvider = PaymentProvider::from($provider);

            $gateway = GatewayFactory::make($paymentProvider);

            $signature = match ($paymentProvider) {
                PaymentProvider::Stripe => (string) $request->header('Stripe-Signature'),
                PaymentProvider::Paddle => (string) $request->header('Paddle-Signature'),
            };

            $dto = $gateway->verifyWebhook($request->getContent(), $signature);
        } catch (Exception $exception) {
            $code = $exception->getCode() === Response::HTTP_BAD_REQUEST
                ? Response::HTTP_BAD_REQUEST
                : Response::HTTP_OK;
            return response()->json(['message' => $exception->getMessage()], $code);
        }
        $order = Order::find($dto->orderId);
        if ($order) {
            $checkoutService->handleWebhook(
                $order,
                $dto->isSuccess(),
                $dto->transactionId,
                $dto->provider->value,
            );
        }

        return response()->json(['status' => 'success']);
    }
}
