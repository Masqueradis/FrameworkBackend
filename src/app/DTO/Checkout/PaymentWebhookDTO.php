<?php

declare(strict_types=1);

namespace App\DTO\Checkout;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

class PaymentWebhookDTO extends Data
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $transactionId,
        #[In(['stripe', 'paddle'])]
        public readonly string $provider,
        #[In(['success', 'failed'])]
        public readonly string $status,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
