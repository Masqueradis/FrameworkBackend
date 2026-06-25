<?php

namespace App\Services\Gateways\Strategy;

use App\DTO\Checkout\PaymentWebhookDTO;
use App\Models\Order;

interface GatewayStrategyInterface
{
    public function createCheckoutUrl(Order $order): string;

    public function verifyWebhook(string $payload, string $signature): PaymentWebhookDTO;
}
