<?php

declare(strict_types=1);

namespace App\DTO;

readonly class ProductFilterDTO
{
    public function __construct(
        public ?int $categoryId = null,
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public ?string $search = null,
        public array $attributes = [],
    ) {}
}
