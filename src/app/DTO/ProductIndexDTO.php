<?php

declare(strict_types=1);

namespace App\DTO;

use App\Casts\DataValueObjectIdCast;
use App\ValueObjects\Id\CategoryId;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class ProductIndexDTO extends Data
{
    public function __construct(
        public int $page = 1,
        #[WithCast(DataValueObjectIdCast::class)]
        public ?CategoryId $categoryId = null,
        #[Min(0)]
        public ?float $minPrice = null,
        #[Min(0)]
        public ?float $maxPrice = null,
        public ?string $search = null,
        /** @var array<string, mixed> */
        public ?array $attributes = null,
    ) {}
}
