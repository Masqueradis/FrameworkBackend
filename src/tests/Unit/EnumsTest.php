<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CommentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
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

    #[Test]
    public function testCorrectlyIdentifiesPendingStatus(): void
    {
        $status = CommentStatus::Pending;

        $this->assertTrue($status->isPending());
        $this->assertFalse($status->isApproved());
        $this->assertFalse($status->isRejected());
    }

    #[Test]
    public function testCorrectlyIdentifiesApprovedStatus(): void
    {
        $status = CommentStatus::Approved;

        $this->assertTrue($status->isApproved());
        $this->assertFalse($status->isPending());
    }

    #[Test]
    public function testCorrectlyIdentifiesRejectedStatus(): void
    {
        $status = CommentStatus::Rejected;

        $this->assertTrue($status->isRejected());
        $this->assertFalse($status->isPending());
    }

    #[Test]
    public function testUserRoleContainsExpectedValues(): void
    {
        $this->assertEquals('admin', UserRole::Admin->value);
        $this->assertEquals('seller', UserRole::Seller->value);
        $this->assertEquals('customer', UserRole::Customer->value);
    }

    #[Test]
    public function testUserStatusContainsExpectedValues(): void
    {
        $this->assertEquals('active', UserStatus::Active->value);
        $this->assertEquals('banned', UserStatus::Banned->value);
    }
}
