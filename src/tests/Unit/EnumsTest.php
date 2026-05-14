<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EnumsTest extends TestCase
{
    #[Test]
    public function testOrderStatusHasCorrectValues(): void
    {
        $this->assertEquals('pending', OrderStatus::Pending->value);
        $this->assertEquals('processing', OrderStatus::Processing->value);
        $this->assertEquals('completed', OrderStatus::Completed->value);
        $this->assertEquals('cancelled', OrderStatus::Cancelled->value);
    }

    #[Test]
    public function testPaymentStatusHasCorrectValues(): void
    {
        $this->assertEquals('pending', PaymentStatus::Pending->value);
        $this->assertEquals('paid', PaymentStatus::Paid->value);
        $this->assertEquals('failed', PaymentStatus::Failed->value);
    }
}
