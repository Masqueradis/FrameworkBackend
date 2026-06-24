<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CommentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    #[Test]
    public function test_order_status_has_correct_values(): void
    {
        $this->assertEquals('pending', OrderStatus::Pending->value);
        $this->assertEquals('processing', OrderStatus::Processing->value);
        $this->assertEquals('completed', OrderStatus::Completed->value);
        $this->assertEquals('cancelled', OrderStatus::Cancelled->value);
    }

    #[Test]
    public function test_payment_status_has_correct_values(): void
    {
        $this->assertEquals('pending', PaymentStatus::Pending->value);
        $this->assertEquals('paid', PaymentStatus::Paid->value);
        $this->assertEquals('failed', PaymentStatus::Failed->value);
    }

    #[Test]
    public function test_correctly_identifies_pending_status(): void
    {
        $status = CommentStatus::Pending;

        $this->assertTrue($status->isPending());
        $this->assertFalse($status->isApproved());
        $this->assertFalse($status->isRejected());
    }

    #[Test]
    public function test_correctly_identifies_approved_status(): void
    {
        $status = CommentStatus::Approved;

        $this->assertTrue($status->isApproved());
        $this->assertFalse($status->isPending());
    }

    #[Test]
    public function test_correctly_identifies_rejected_status(): void
    {
        $status = CommentStatus::Rejected;

        $this->assertTrue($status->isRejected());
        $this->assertFalse($status->isPending());
    }

    #[Test]
    public function test_user_role_contains_expected_values(): void
    {
        $this->assertEquals('admin', UserRole::Admin->value);
        $this->assertEquals('seller', UserRole::Seller->value);
        $this->assertEquals('customer', UserRole::Customer->value);
    }

    #[Test]
    public function test_user_status_contains_expected_values(): void
    {
        $this->assertEquals('active', UserStatus::Active->value);
        $this->assertEquals('banned', UserStatus::Banned->value);
    }
}
