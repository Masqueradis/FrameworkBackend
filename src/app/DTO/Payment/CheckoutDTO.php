<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class CheckoutDTO extends Data
{
    public function __construct(
        public readonly string $customerName,
        #[Email]
        public readonly string $customerEmail,
        public readonly ?string $customerPhone,
        public readonly string $shippingAddress,
        #[In(['stripe', 'paypal', 'cash'])]
        public readonly string $paymentProvider,
    ) {}
}
