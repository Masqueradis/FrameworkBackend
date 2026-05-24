<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\User\UpdateProfileDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function updateProfile(User $user, UpdateProfileDTO $dto): bool
    {
        $attributesToUpdate = collect([
            'name' => $dto->name,
            'avatar_path' => $this->resolveAvatarPath($user, $dto->avatar),
        ])->filter()->toArray();

        return !empty($attributesToUpdate) && $this->userRepository->update($user, $attributesToUpdate);
    }

    public function deleteAccount(User $user): bool
    {
        $this->deleteOldAvatar($user->avatar_path);

        return $this->userRepository->delete($user);
    }

    private function resolveAvatarPath(User $user, ?UploadedFile $newAvatar): ?string
    {
        if (!$newAvatar) {
            return null;
        }

        $this->deleteOldAvatar($user->avatar_path);

        return $newAvatar->store('avatars', 's3');
    }

    private function deleteOldAvatar(?string $path): void
    {
        if ($path) {
            Storage::disk('s3')->delete($path);
        }
    }
}
