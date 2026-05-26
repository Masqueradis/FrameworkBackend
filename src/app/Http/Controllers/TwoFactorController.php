<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends ApiController
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorAuthService,
        private readonly UserRepository $userRepository,
    ) {}

    public function generate(): JsonResponse
    {
        $user = auth()->user();
        $secret = $this->twoFactorAuthService->generateSecret();

        $this->userRepository->update2faSecret($user->id, $secret);

        $qrCodeUrl = $this->twoFactorAuthService->getQrCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }
}
