<?php

declare (strict_types=1);

namespace Tests\Unit;

use App\Models\Order;
use App\Services\Gateways\FakePaymentGateway;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FakePaymentGatewayTest extends TestCase
{
    #[Test]
    public function testReturnsSuccessForValidToken(): void
    {
        $gateway = new FakePaymentGateway();
        $order = new Order(['total_amount_cents' => 1000]);

        $result = $gateway->charge($order, 'tok_4242424242');

        $this->assertTrue($result->isSuccess);
        $this->assertNotNull($result->transactionId);
        $this->assertEquals('Payment successful', $result->message);
    }

    #[Test]
    public function testReturnsFailureForErrorToken(): void
    {
        $gateway = new FakePaymentGateway;
        $order = new Order(['total_amount_cents' => 1000]);

        $result = $gateway->charge($order, 'tok_error_card');

        $this->assertFalse($result->isSuccess);
        $this->assertNull($result->transactionId);
        $this->assertEquals('Payment declined by bank', $result->message);
    }
}
