<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Casts\DataValueObjectIdCast;
use App\ValueObjects\CategoryId;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CategorySaveData extends Data
{
    public function __construct(
        public string $name,
        #[WithCast(DataValueObjectIdCast::class)]
        public ?CategoryId $parent_id = null,
    ) {}
}
