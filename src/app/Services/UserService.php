<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function assignRole(User $performer, User $targetUser, UserRole $role): void
    {
        $performerRole = $performer->getRoleNames()->first() ?? 'customer';
        $targetRoleValue = $role->value;

        $allowedRoles = [
            'admin' => ['admin', 'manager', 'seller', 'customer'],
            'manager' => ['seller', 'customer'],
        ];

        if (! in_array($targetRoleValue, $allowedRoles[$performerRole] ?? [])
            || ($targetUser->hasRole('admin') && $performerRole !== 'admin')) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $targetUser->syncRoles([$targetRoleValue]);
    }

    public function banUser(User $user): bool
    {
        return $this->userRepository->updateStatus($user->id, UserStatus::Banned);
    }

    public function unbanUser(User $user): bool
    {
        return $this->userRepository->updateStatus($user->id, UserStatus::Active);
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function getPaginatedUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }
}
