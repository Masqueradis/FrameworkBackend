<?php

namespace Tests\Unit;

use App\ValueObjects\Cart\CartQuantity;
use App\ValueObjects\Cart\Money;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

class CartValueObjectsTest extends TestCase
{
    #[Test]
    public function testMoneyStoresCentsAndOutputsDollars(): void
    {
        $money = new Money(1550);

        $this->assertEquals(1550, $money->getCents());
        $this->assertEquals(15.50, $money->getDollars());
        $this->assertEquals('$15.50', $money->getFormated());
    }

    #[Test]
    public function testMoneyThrowsExceptionOnNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative.');

        new Money(-10);
    }

    #[Test]
    public function testMoneyCanBeAdded(): void
    {
        $money = new Money(1000);
        $newMoney = $money->add(new Money(500));

        $this->assertEquals(1500, $newMoney->getCents());
        $this->assertNotSame($money, $newMoney);
    }

    #[Test]
    public function testMoneyCanBeMultiplied(): void
    {
        $money = new Money(1000);
        $newMoney = $money->multiply(2);

        $this->assertEquals(2000, $newMoney->getCents());
    }

    #[Test]
    public function testCartQuantityStoresValidAmount(): void
    {
        $quantity = new CartQuantity(5);
        $this->assertEquals(5, $quantity->getValue());
    }

    #[Test]
    public function testCartQuantityThrowsExceptionIfLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be between 1 and 99.');

        new CartQuantity(0);
    }

    #[Test]
    public function testCartQuantityThrowsExceptionIfMoreThanNinetyNine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be between 1 and 99.');

        new CartQuantity(100);
    }
}
