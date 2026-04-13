<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Casts\ValueObjectIdCast;
use App\ValueObjects\AbstractId;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DummyValueObjectId extends AbstractId {}
class AnotherDummyId extends AbstractId {}
class DummyModel extends Model {}

class ValueObjectIdCastTest extends TestCase
{
    private ValueObjectIdCast $cast;
    private DummyModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cast = new ValueObjectIdCast(DummyValueObjectId::class);

        $this->model = new DummyModel();
    }

    public function testGetReturnsNullWhenValueIsNull(): void
    {
        $this->assertNull($this->cast->get($this->model, 'id', null, []));
    }

    public function testGetReturnsValueObjectWhenValueIsNumeric(): void
    {
        $result = $this->cast->get($this->model, 'id', 42, []);

        $this->assertInstanceOf(DummyValueObjectId::class, $result);

        $this->assertEquals(42, $result->value);
    }

    public function testGetConvertsFromAnotherAbstractId(): void
    {
        $otherVo = new AnotherDummyId(42);

        $result = $this->cast->get($this->model, 'id', $otherVo, []);

        $this->assertInstanceOf(DummyValueObjectId::class, $result);
        $this->assertEquals(42, $result->value);
    }

    public function testSetReturnsNullWhenValueIsNull(): void
    {
        $this->assertNull($this->cast->set($this->model, 'id', null, []));
    }

    public function testSetReturnsIntegerWhenValueIsTargetObject(): void
    {
        $vo = new DummyValueObjectId(15);

        $this->assertSame(15, $this->cast->set($this->model, 'id', $vo, []));
    }

    public function testSetReturnsIntegerWhenValueIsAnotherAbstractId(): void
    {
        $otherVo = new AnotherDummyId(15);

        $this->assertSame(15, $this->cast->set($this->model, 'id', $otherVo, []));
    }

    public function testSetReturnsIntegerWhenValueIsNumericString(): void
    {
        $this->assertSame(99, $this->cast->set($this->model, 'id', '99', []));
    }

    public function testSetThrowsExceptionOnInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cast->set($this->model, 'id', ['invalid_array'], []);
    }
}
