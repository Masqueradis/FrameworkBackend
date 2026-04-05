<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ProductSaveData extends Data
{
    public function __construct(
        public int $categoryId,
        public string $name,
        #[Min(0)]
        public float $price,
        #[Min(0)]
        public int $stock = 0,
        public bool $available = true,
        public ?string $description = null,
        public ?string $sku = null,
        /** @var array<string, mixed> */
        public ?array $attributes = null,
    ) {}
}
