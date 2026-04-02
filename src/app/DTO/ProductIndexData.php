<?php

declare(strict_types=1);

namespace App\DTO;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Min;

class ProductIndexData extends Data
{
    public function __construct(
        public int $page = 1,
        public ?int $categoryId = null,
        #[Min(0)]
        public ?float $minPrice = null,
        #[Min(0)]
        public ?float $maxPrice = null,
        public ?string $search = null,
        public ?array $attributes = null,
    ) {}
}
