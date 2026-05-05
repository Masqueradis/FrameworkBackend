<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\ValueObjects\Id\UserId;

class UserRepository
{
    public function findById(UserId $id): ?User
    {
        return User::find($id->value);
    }

    /**
     * @param array<string, mixed> $data
     * @return User
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * @param string|array<int, string> $role
     */
    public function assignRole(User $user, string|array $role): void
    {
        $user->assignRole($role);
    }
}
