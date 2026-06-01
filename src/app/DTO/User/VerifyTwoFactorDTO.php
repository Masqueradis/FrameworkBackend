<?php

declare(strict_types=1);

namespace App\DTO\User;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class VerifyTwoFactorDTO extends Data
{
    public function __construct(
        #[Required]
        #[StringType]
        public string $otp,
    ) {}
}
