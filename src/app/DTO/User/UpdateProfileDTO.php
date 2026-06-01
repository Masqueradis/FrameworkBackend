<?php

declare(strict_types=1);

namespace App\DTO\User;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Data;

class UpdateProfileDTO extends Data
{
    public function __construct(
        #[Max(255)]
        public readonly ?string $name,
        #[File]
        #[Mimes('jpg', 'png')]
        #[Max(2048)]
        public readonly ?UploadedFile $avatar = null,
    ) {}
}
