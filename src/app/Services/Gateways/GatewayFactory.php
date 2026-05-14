<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Services\Gateways\Strategy\GatewayStrategyInterface;

class GatewayFactory
{
    public static function make(string $provider): GatewayStrategyInterface
    {
        return match ($provider) {
            'stripe' => app(StripeGateway::class),
            'paddle' => app(PaddleGateway::class),
            default => throw new \InvalidArgumentException('Unknown provider: ' . $provider),
        };
    }
}
