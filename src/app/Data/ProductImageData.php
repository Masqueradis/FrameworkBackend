<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ProductImageData extends Data
{
    public function __construct(
        public string $name,
        public int $price,
        #[DataCollectionOf(ProductImageData::class)]
        public DataCollection $images,
    ) {}
}
