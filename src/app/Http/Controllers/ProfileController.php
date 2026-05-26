<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\User\UpdateProfileDTO;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends ApiController
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function update(UpdateProfileDTO $dto): JsonResponse
    {
        $this->profileService->updateProfile(auth()->user(), $dto);

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function destroy(): JsonResponse
    {
        $this->profileService->deleteAccount(auth()->user());

        return response()->json(['message' => 'Account deleted successfully.']);
    }
}
