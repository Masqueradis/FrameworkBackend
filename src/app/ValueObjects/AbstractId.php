<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

abstract class AbstractId
{
    public function __construct(public readonly int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException(class_basename($this) . " must be greater than 0");
        }
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
