<?php

declare(strict_types=1);

namespace App\DTO\Checkout;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Spatie\LaravelData\Data;

class PaymentWebhookDTO extends Data
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $transactionId,
        public readonly PaymentProvider $provider,
        public readonly PaymentStatus $status,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === PaymentStatus::Success;
    }
}
