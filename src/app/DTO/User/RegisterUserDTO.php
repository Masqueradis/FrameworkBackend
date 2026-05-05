<?php

declare(strict_types=1);

namespace App\DTO\User;

use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class RegisterUserDTO extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        #[Required, Email]
        #[Unique('users', 'email')]
        public string $email,
        #[Required, Min(8), Confirmed]
        public string $password,
        public ?string $password_confirmation = null,
    ) {}
}
