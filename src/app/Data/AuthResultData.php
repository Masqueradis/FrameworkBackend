<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;

class AuthResultData extends Data
{
    public function __construct(
        public User $user,
        public string $accessToken,
    ) {}
}
