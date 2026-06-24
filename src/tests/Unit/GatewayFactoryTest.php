<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentProvider;
use App\Services\Gateways\GatewayFactory;
use App\Services\Gateways\PaddleGateway;
use App\Services\Gateways\StripeGateway;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GatewayFactoryTest extends TestCase
{
    #[Test]
    public function is_creates_stripe_gateway(): void
    {
        $gateway = GatewayFactory::make(PaymentProvider::Stripe);
        $this->assertInstanceOf(StripeGateway::class, $gateway);
    }

    #[Test]
    public function test_creates_paddle_gateway(): void
    {
        $gateway = GatewayFactory::make(PaymentProvider::Paddle);
        $this->assertInstanceOf(PaddleGateway::class, $gateway);
    }
}
