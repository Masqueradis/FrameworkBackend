<?php

declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Services\Gateways\GatewayFactory;
use App\Services\Gateways\PaddleGateway;
use App\Services\Gateways\StripeGateway;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GatewayFactoryTest extends TestCase
{
    #[Test]
    public function isCreatesStripeGateway(): void
    {
        $gateway = GatewayFactory::make('stripe');
        $this->assertInstanceOf(StripeGateway::class, $gateway);
    }

    #[Test]
    public function itCreatesPaddleGateway(): void
    {
        $gateway = GatewayFactory::make('paddle');
        $this->assertInstanceOf(PaddleGateway::class, $gateway);
    }

    #[Test]
    public function itThrowsExceptionOnInvalidGateway(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown provider: unknown_crypto');

        GatewayFactory::make('unknown_crypto');
    }
}
