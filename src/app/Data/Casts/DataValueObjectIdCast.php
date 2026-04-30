<?php

declare(strict_types=1);

namespace App\Data\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Types\NamedType;

class DataValueObjectIdCast implements Cast
{
    /**
     * @param DataProperty $property
     * @param mixed $value
     * @param array<string, mixed> $context
     * @param CreationContext<BaseData<mixed, mixed, int|string>> $creationContext
     * @return mixed
     */
    public function cast(DataProperty $property, mixed $value, array $context, CreationContext $creationContext): mixed
    {
        $type = $property->type->type;
        if ($type instanceof NamedType) {
            $className = $type->name;
            return new $className((int) $value);
        }

        return $value;
    }
}
