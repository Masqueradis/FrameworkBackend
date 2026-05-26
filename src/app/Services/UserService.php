<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\UserRepository;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function assignRole(User $user, UserRole $role): void
    {
        $user->syncRoles($role->value);
    }

    public function banUser(User $user): bool
    {
        return $this->userRepository->updateStatus($user->id, UserStatus::Banned);
    }

    public function unbanUser(User $user): bool
    {
        return $this->userRepository->updateStatus($user->id, UserStatus::Active);
    }
}
