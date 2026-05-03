<?php

namespace Tests\Unit;

use App\Casts\DataValueObjectIdCast;
use PHPUnit\Framework\Attributes\Test;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Tests\TestCase;

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
