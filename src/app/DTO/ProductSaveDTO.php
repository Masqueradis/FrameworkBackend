<?php

declare(strict_types=1);

namespace App\DTO;

readonly class ProductSaveDTO
{
    public function __construct(
        public int $categoryId,
        public string $name,
        public int $price,
        public int $stock,
        public bool $available,
        public array $attributes = [],
        public ?string $description = null,
        public ?string $sku = null,
    ) {}
}
