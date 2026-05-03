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
    public function testThrowsExceptionIfValueIsZeroOrNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DummyId must be greater than 0');

        new DummyId(0);
    }

    #[Test]
    public function testEqualsReturnsTrueForSameClassAndValue(): void
    {
        $id1 = new DummyId(1);
        $id2 = new DummyId(1);

        $this->assertTrue($id1->equals($id2));
    }

    #[Test]
    public function testEqualsReturnsFalseForDifferentClassAndValue(): void
    {
        $id = new DummyId(1);
        $difValue = new DummyId(2);
        $difClass = new AnotherDummyId(1);

        $this->assertFalse($id->equals($difClass));
        $this->assertFalse($id->equals($difValue));
    }

    #[Test]
    public function testCanBeCastToString(): void
    {
        $id = new DummyId(1);
        $this->assertEquals('1', (string) $id);
    }
}
