<?php

declare(strict_types=1);

namespace App\DTO;

use App\Casts\DataValueObjectIdCast;
use App\ValueObjects\Id\CategoryId;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CategorySaveDTO extends Data
{
    public function __construct(
        public string $name,
        #[WithCast(DataValueObjectIdCast::class)]
        public ?CategoryId $parent_id = null,
    ) {}
}
