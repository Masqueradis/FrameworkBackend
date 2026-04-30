<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Image;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

class UploadImageData extends Data
{
    public function __construct(
        #[Image]
        #[Max(2048)]
        public UploadedFile $image,
        public bool $is_primary = false,
        public int $position = 0,
    ) {}

    /**
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        if (isset($properties['is_primary'])) {
            $properties['is_primary'] = filter_var($properties['is_primary'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($properties['position'])) {
            $properties['position'] = (int) $properties['position'];
        }

        return $properties;
    }
}
