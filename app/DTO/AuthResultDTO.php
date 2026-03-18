<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\User;

readonly class AuthResultDTO
{
    public function __construct(
        public User $user,
        public string $accessToken,
    ) {}
}
