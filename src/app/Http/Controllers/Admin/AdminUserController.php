<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTO\User\AssignRoleDTO;
use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminUserController extends ApiController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(): View
    {
        $users = $this->userService->getPaginatedUsers(15);

        return view('admin.users.index', compact('users'));
    }

    public function ban(User $user): RedirectResponse
    {
        $this->userService->banUser($user);

        return back()->with(['message' => "User {$user->name} has been banned."]);
    }

    public function unban(User $user): RedirectResponse
    {
        $this->userService->unbanUser($user);

        return back()->with(['message' => "User {$user->name} has been unbanned."]);
    }

    public function assignRole(User $user, AssignRoleDTO $dto): RedirectResponse
    {
        $performer = auth()->user();

        assert($performer instanceof User);

        $this->userService->assignRole($performer, $user, $dto->role);

        return back()->with(['message' => 'User role successfully changed.']);
    }
}
