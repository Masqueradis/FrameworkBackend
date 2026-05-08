<?php

declare(strict_types=1);

namespace App\DTO\Checkout;

use Spatie\LaravelData\Data;

class PaymentResultDTO extends Data
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly ?string $transactionId,
        public readonly string $message,
    ) {}
}
