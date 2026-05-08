<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\DTO\Checkout\PaymentResultDTO;
use App\Services\Gateways\Contracts\PaymentGatewayInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Stripe;
use App\Models\Order;

class StripeGateway implements PaymentGatewayInterface
{
    public function charge(Order $order, string $paymentToken): PaymentResultDTO
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $charge = Charge::create([
                'amount' => $order->total_amount_cents,
                'currency' => 'usd',
                'source' => $paymentToken,
                'description' => 'Order #' . $order->id,
            ]);

            return new PaymentResultDTO(
                isSuccess: $charge->status === 'succeeded',
                transactionId: $charge->id,
                message: 'Payment successful',
            );
        } catch (Exception $exception) {
            Log::error('Stripe payment failed: ' . $exception->getMessage());

            return new PaymentResultDTO(
                isSuccess: false,
                transactionId: null,
                message: $exception->getMessage(),
            );
        }
    }
}
