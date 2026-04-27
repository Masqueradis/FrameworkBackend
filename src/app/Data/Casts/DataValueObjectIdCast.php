<?php

declare(strict_types=1);

namespace App\Data\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Types\NamedType;

class DataValueObjectIdCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $context, CreationContext $creationContext): mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $property->type->type;
        $className = $type->name;

        return new $className((int) $value);
    }
}
