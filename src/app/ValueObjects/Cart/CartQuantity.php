<?php

declare(strict_types=1);

namespace App\ValueObjects\Cart;

use InvalidArgumentException;

class CartQuantity
{
    public const MIN_QUANTITY = 1;
    public const MAX_QUANTITY = 99;

    public function __construct(private int $value)
    {
        if ($value < self::MIN_QUANTITY || $value > self::MAX_QUANTITY) {
            throw new InvalidArgumentException('Quantity must be between 1 and 99.');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function add(int $amount): self
    {
        return new self($this->value + $amount);
    }
}
