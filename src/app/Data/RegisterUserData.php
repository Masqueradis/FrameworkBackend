<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Confirmed;

class RegisterUserData extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        #[Required, Email]
        public string $email,
        #[Required, Min(8), Confirmed]
        public string $password,
        public ?string $password_confirmation = null,
    ) {}
}
