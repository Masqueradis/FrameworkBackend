<?php

declare(strict_types=1);

namespace App\DTO\Cart;

use App\ValueObjects\Cart\CartQuantity;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class UpdateCartItemDTO extends Data
{
    public function __construct(
        public readonly int $cartItemId,
        #[Min(1), Max(99)]
        public readonly int $quantity,
    ) {}
}
