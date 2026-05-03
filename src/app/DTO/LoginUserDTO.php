<?php

declare(strict_types=1);

namespace App\DTO;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;

class LoginUserDTO extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required]
        public string $password,
    ) {}
}
