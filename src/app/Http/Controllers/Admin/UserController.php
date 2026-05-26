<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTO\User\AssignRoleDTO;
use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends ApiController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function ban(User $user): JsonResponse
    {
        $this->userService->banUser($user);

        return response()->json(['message' => "User {$user->name} has been banned."]);
    }

    public function assignRole(User $user, AssignRoleDTO $dto): JsonResponse
    {
        $this->userService->assignRole($user, $dto->role);

        return response()->json(['message' => 'User role successfully changed.']);
    }
}
