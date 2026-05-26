<?php

declare(strict_types=1);

namespace App\DTO\User;

use App\Enums\UserRole;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Data;

class AssignRoleDTO extends Data
{
    public function __construct(
        #[Enum(UserRole::class)]
        public readonly UserRole $role,
    ) {}
}
