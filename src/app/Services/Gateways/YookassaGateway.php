<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\DTO\Checkout\PaymentResultDTO;
use App\Models\Order;
use App\Services\Gateways\Contracts\PaymentGatewayInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use YooKassa\Client;

class YookassaGateway implements PaymentGatewayInterface
{
    public function charge(Order $order, string $paymentToken): PaymentResultDTO
    {
        $client = new Client();
        $client->setAuth(config('services.yookassa.shop_id'), config('services.yookassa.secret'));

        try {
            $amountInRubles = number_format($order->total_amount_cents / 100, 2, '.', '');

            $payment = $client->createPayment([
                'amount' => [
                    'value' => $amountInRubles,
                    'currency' => 'RUB',
                ],
                'payment_token' => $paymentToken,
                'capture' => true,
                'description' => 'Order №' . $order->id,
            ],
              uniqid('', true)
            );

            return new PaymentResultDTO(
                isSuccess: $payment?->getStatus() === 'succeeded',
                transactionId: $payment?->getId(),
                message: 'Payment successful',
            );
        }   catch (Exception $exception) {
            Log::error('Yookassa payment failed: ' . $exception->getMessage());

            return new PaymentResultDTO(
                isSuccess: false,
                transactionId: null,
                message: $exception->getMessage(),
            );
        }
    }
}
