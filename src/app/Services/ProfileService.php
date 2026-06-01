<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\User\UpdateProfileDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

readonly class ProfileService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function updateProfile(User $user, UpdateProfileDTO $dto): bool
    {
        $attributesToUpdate = collect([
            'name' => $dto->name,
            'avatar_path' => $this->resolveAvatarPath($user, $dto->avatar),
        ])->filter()->toArray();

        return !empty($attributesToUpdate) && $this->userRepository->update($user, $attributesToUpdate);
    }

    public function deleteAccount(User $user): ?bool
    {
        $this->deleteAvatar($user);

        return $this->userRepository->delete($user);
    }

    private function resolveAvatarPath(User $user, ?UploadedFile $newAvatar): ?string
    {
        if (!$newAvatar) {
            return null;
        }

        if (!$newAvatar->isValid()) {
            throw new \RuntimeException('Upload error: ' . $newAvatar->getErrorMessage());
        }

        $this->deleteAvatar($user);

        $path = $newAvatar->store('avatars', 'minio');

        if ($path === false) {
            throw new \RuntimeException('MinIO rejected the connection instantly. Check AWS_ENDPOINT in .env and make sure the MinIO container is running.');
        }

        return $path;
    }

    public function deleteAvatar(User $user): void
    {
        if (!$user->avatar_path) {
            return;
        }

        Storage::disk('minio')->delete($user->avatar_path);

        $this->userRepository->updateAvatar($user->id, null);

        $user->avatar_path = null;
    }
}
