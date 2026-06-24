<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

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
    public function test_returns_original_value_if_type_is_not_named_type(): void
    {
        $data = DummyUnionDto::from(['mixed_id' => 123]);

        $this->assertEquals(123, $data->mixed_id);
    }
}
