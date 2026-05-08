<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Checkout\PaymentWebhookDTO;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends ApiController
{
    public function __construct(private readonly CheckoutService $checkoutService) {}

    public function handleFakePayment(PaymentWebhookDTO $data): JsonResponse
    {
        if($data->signature !== 'secret_hash_123') {
            Log::warning('Webhook invalid signature', $data->toArray());

            return response()->json(['error' => 'Invalid signature'], Response::HTTP_FORBIDDEN);
        }

        $order = Order::findOrFail($data->orderId);

        $isSuccess = $data->status === 'success';

        $this->checkoutService->handleWebhook(
            $order,
            $isSuccess,
            $data->transactionId,
            'fake_provider'
        );

        return response()->json(['message' => 'Webhook processed']);
    }
}
