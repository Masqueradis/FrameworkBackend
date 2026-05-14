<?php

namespace App\Services\Gateways\Strategy;

use App\DTO\Checkout\PaymentWebhookDTO;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;

interface GatewayStrategyInterface
{
    public function createCheckoutUrl(Order $order): string;
    public function verifyWebhook(Request $request): PaymentWebhookDTO;
}
