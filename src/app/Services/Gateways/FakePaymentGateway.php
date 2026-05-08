<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\DTO\Checkout\PaymentResultDTO;
use App\Models\Order;
use App\Services\Gateways\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Str;

class FakePaymentGateway implements PaymentGatewayInterface
{
    public function charge(Order $order, string $paymentToken): PaymentResultDTO
    {
        if (str_contains($paymentToken, 'error')) {
            return new PaymentResultDTO(false, null, 'Payment declined by bank');
        }

        return new PaymentResultDTO(
            true,
            'fake_txn' . Str::random(10),
            'Payment successful',
        );
    }
}
