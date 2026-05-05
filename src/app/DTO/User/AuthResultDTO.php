<?php

declare(strict_types=1);

namespace App\DTO\User;

use App\Models\User;
use Spatie\LaravelData\Data;

class AuthResultDTO extends Data
{
    public function __construct(
        public User $user,
        public string $accessToken,
    ) {}
}
