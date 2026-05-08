<?php

declare(strict_types=1);

namespace App\Services\Gateways\Contracts;

use App\DTO\Checkout\PaymentResultDTO;
use App\Models\Order;

interface PaymentGatewayInterface
{
    public function charge(Order $order, string $paymentToken): PaymentResultDTO;
}
