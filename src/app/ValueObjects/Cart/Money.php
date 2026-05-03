<?php

declare(strict_types=1);

namespace App\ValueObjects\Cart;

use InvalidArgumentException;

class Money
{
    public function __construct(private int $cents)
    {
        if($cents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative.');
        }
    }

    public function getCents(): int
    {
        return $this->cents;
    }

    public function getDollars(): float
    {
        return $this->cents / 100;
    }

    public function getFormated(): string
    {
        return '$' . number_format($this->getDollars(), 2);
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->getCents());
    }

    public function multiply(int $multiplier): self
    {
        if($multiplier < 0) {
            throw new InvalidArgumentException('Amount cannot be negative.');
        }
        return new self($this->cents * $multiplier);
    }
}
