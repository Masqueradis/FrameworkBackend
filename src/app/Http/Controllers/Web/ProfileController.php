<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\DTO\User\UpdateProfileDTO;
use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ProfileService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends ApiController
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        assert($user instanceof User);

        $orders = $this->orderService->getUserOrdersHistory($user->id, 5);

        return view('profile.dashboard', compact('orders'));
    }

    public function edit(): View
    {
        return view('profile.edit');
    }

    public function update(UpdateProfileDTO $dto): RedirectResponse
    {
        $user = auth()->user();
        assert($user instanceof User);

        $this->profileService->updateProfile($user, $dto);

        return back()->with(['message' => 'Profile updated successfully.']);
    }

    public function destroy(): RedirectResponse
    {
        $user = auth()->user();
        assert($user instanceof User);

        $this->profileService->deleteAccount($user);

        return back()->with(['message' => 'Account deleted successfully.']);
    }

    public function destroyAvatar(): RedirectResponse
    {
        $user = auth()->user();
        assert($user instanceof User);

        $this->profileService->deleteAvatar($user);

        return back()->with('success', 'Avatar deleted successfully.');
    }
}
