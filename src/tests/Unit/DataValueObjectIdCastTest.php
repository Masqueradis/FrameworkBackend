<?php

namespace Tests\Unit;

use App\Data\Casts\DataValueObjectIdCast;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

class DummyUnionDto extends Data
{
    public function __construct(
        #[WithCast(DataValueObjectIdCast::class)]
        public int|string $mixed_id
    ) {}
}

class DataValueObjectIdCastTest extends TestCase
{
    #[Test]
    public function testReturnsOriginalValueIfTypeIsNotNamedType(): void
    {
        $dto = DummyUnionDto::from(['mixed_id' => 123]);

        $this->assertEquals(123, $dto->mixed_id);
    }
}
