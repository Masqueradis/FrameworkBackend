<?php

declare(strict_types=1);

namespace Tests\Unit;

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
        $gateway = GatewayFactory::make('stripe');
        $this->assertInstanceOf(StripeGateway::class, $gateway);
    }

    #[Test]
    public function testCreatesPaddleGateway(): void
    {
        $gateway = GatewayFactory::make('paddle');
        $this->assertInstanceOf(PaddleGateway::class, $gateway);
    }

    #[Test]
    public function testThrowsExceptionOnInvalidGateway(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown provider: unknown_crypto');

        GatewayFactory::make('unknown_crypto');
    }
}
