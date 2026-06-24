<?php

namespace Tests\Unit\ValueObjects;

use App\Casts\MoneyCast;
use App\Models\CartItem;
use App\ValueObjects\Cart\CartQuantity;
use App\ValueObjects\Cart\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CartValueObjectsTest extends TestCase
{
    #[Test]
    public function test_money_stores_cents_and_outputs_dollars(): void
    {
        $money = new Money(1550);

        $this->assertEquals(1550, $money->getCents());
        $this->assertEquals(15.50, $money->getDollars());
        $this->assertEquals('$15.50', $money->getFormated());
    }

    #[Test]
    public function test_money_throws_exception_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative.');

        new Money(-10);
    }

    #[Test]
    public function test_money_can_be_added(): void
    {
        $money = new Money(1000);
        $newMoney = $money->add(new Money(500));

        $this->assertEquals(1500, $newMoney->getCents());
        $this->assertNotSame($money, $newMoney);
    }

    #[Test]
    public function test_money_can_be_multiplied(): void
    {
        $money = new Money(1000);
        $newMoney = $money->multiply(2);

        $this->assertEquals(2000, $newMoney->getCents());
    }

    #[Test]
    public function test_cart_quantity_stores_valid_amount(): void
    {
        $quantity = new CartQuantity(5);
        $this->assertEquals(5, $quantity->getValue());
    }

    #[Test]
    public function test_cart_quantity_throws_exception_if_less_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be between 1 and 99.');

        new CartQuantity(0);
    }

    #[Test]
    public function test_cart_quantity_throws_exception_if_more_than_ninety_nine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be between 1 and 99.');

        new CartQuantity(100);
    }

    #[Test]
    public function test_cart_quantity_can_be_added(): void
    {
        $quantity = new CartQuantity(5);
        $newQuantity = $quantity->add(10);
        $this->assertEquals(15, $newQuantity->getValue());
        $this->assertEquals(5, $quantity->getValue());
    }

    #[Test]
    public function test_money_throws_exception_if_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative.');
        $money = new Money(500);
        $money->multiply(-2);
    }

    #[Test]
    public function test_money_cast_returns_null_when_setting_null(): void
    {
        $cast = new MoneyCast;
        $model = new CartItem;

        $result = $cast->set($model, 'price', null, []);

        $this->assertNull($result);
    }
}
