<?php

declare(strict_types=1);

namespace App\DTO\Product;

use App\Casts\DataValueObjectIdCast;
use App\ValueObjects\Id\CategoryId;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

class ProductIndexDTO extends Data
{
    public function __construct(
        public int $page = 1,
        #[MapInputName('category_id')]
        #[WithCast(DataValueObjectIdCast::class)]
        public ?CategoryId $categoryId = null,
        #[MapInputName('min_price')]
        #[Min(0)]
        public ?float $minPrice = null,
        #[MapInputName('max_price')]
        #[Min(0)]
        public ?float $maxPrice = null,
        public ?string $search = null,
        /** @var array<string, mixed> */
        public ?array $attributes = null,
    ) {}
}
