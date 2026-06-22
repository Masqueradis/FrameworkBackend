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
    public function isCreatesStripeGateway(): void
    {
        $gateway = GatewayFactory::make(PaymentProvider::Stripe);
        $this->assertInstanceOf(StripeGateway::class, $gateway);
    }

    #[Test]
    public function testCreatesPaddleGateway(): void
    {
        $gateway = GatewayFactory::make(PaymentProvider::Paddle);
        $this->assertInstanceOf(PaddleGateway::class, $gateway);
    }
}
