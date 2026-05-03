<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Casts\DataValueObjectIdCast;
use App\ValueObjects\CategoryId;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ProductSaveData extends Data
{
    public function __construct(
        #[WithCast(DataValueObjectIdCast::class)]
        public CategoryId $categoryId,
        public string $name,
        #[Min(0)]
        public float $price,
        #[Min(0)]
        public int $stock = 0,
        public bool $available = true,
        public ?string $description = null,
        public ?string $sku = null,
        /** @var array<string, mixed> */
        public ?array $attributes = null,
        /** @var array<int, UploadedFile|null> */
        #[Max(10)]
        public ?array $images = null,
        /** @var array<int, string>|null */
        public ?array $attribute_keys = null,
        /** @var array<int, string>|null */
        public ?array $attribute_values = null,
    ) {}

    /**
     * @param mixed ...$args
     * @return array<string, string>
     */
    public static function messages(...$args): array
    {
        return [
            'images.max' => 'You cannot upload more than 10 images at once.',
        ];
    }

    /**
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        $attributes = [];
        $keys = $properties['attribute_keys'] ?? [];
        $values = $properties['attribute_values'] ?? [];

        if (is_array($keys) && is_array($values)) {
            foreach ($keys as $index => $key) {
                if (!empty($key)) {
                    $attributes[$key] = $values[$index] ?? '';
                }
            }
        }

        $properties['attributes'] = $attributes;

        unset($properties['attribute_keys'], $properties['attribute_values']);

        if (!isset($properties['available'])) {
            $properties['available'] = false;
        }

        return $properties;
    }
}
