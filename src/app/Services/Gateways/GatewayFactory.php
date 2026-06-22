<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Enums\PaymentProvider;
use App\Services\Gateways\Strategy\GatewayStrategyInterface;

class GatewayFactory
{
    public static function make(PaymentProvider $provider): GatewayStrategyInterface
    {
        return match ($provider) {
            PaymentProvider::Stripe => app(StripeGateway::class),
            PaymentProvider::Paddle => app(PaddleGateway::class),
        };
    }
}
