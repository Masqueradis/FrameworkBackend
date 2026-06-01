<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\ValueObjects\Id\UserId;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Utils;

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

    /**
     * @param User $user
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function updateAvatar(int $userId, ?string $avatarPath): ?bool
    {
        return User::where('id', $userId)->update([
            'avatar_path' => $avatarPath
        ]) > 0;
    }

    public function delete(User $user): ?bool
    {
        return $user->delete();
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

    /**
     * @param UserRole $role
     * @return Collection<int, User>
     */
    public function getByRole(UserRole $role): Collection
    {
        return User::role($role->value)->get();
    }

    public function updateStatus(int $userId, UserStatus $status): bool
    {
        return User::where('id', $userId)->update([
            'status' => $status->value
        ]) > 0;
    }

    public function update2faSecret(int $userId, ?string $secret): bool
    {
        return User::where('id', $userId)->update([
            'google2fa_secret' => $secret
        ]) > 0;
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::orderBy('id', 'desc')->paginate($perPage);
    }

    public function countAll(): int
    {
        return User::count();
    }
}
