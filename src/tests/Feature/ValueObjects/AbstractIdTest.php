<?php

namespace Feature\ValueObjects;

use App\ValueObjects\Id\AbstractId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DummyId extends AbstractId {}
class AnotherDummyId extends AbstractId {}

class AbstractIdTest extends TestCase
{
    #[Test]
    public function test_throws_exception_if_value_is_zero_or_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DummyId must be greater than 0');

        new DummyId(0);
    }

    #[Test]
    public function test_equals_returns_true_for_same_class_and_value(): void
    {
        $id1 = new DummyId(1);
        $id2 = new DummyId(1);

        $this->assertTrue($id1->equals($id2));
    }

    #[Test]
    public function test_equals_returns_false_for_different_class_and_value(): void
    {
        $id = new DummyId(1);
        $difValue = new DummyId(2);
        $difClass = new AnotherDummyId(1);

        $this->assertFalse($id->equals($difClass));
        $this->assertFalse($id->equals($difValue));
    }

    #[Test]
    public function test_can_be_cast_to_string(): void
    {
        $id = new DummyId(1);
        $this->assertEquals('1', (string) $id);
    }
}
