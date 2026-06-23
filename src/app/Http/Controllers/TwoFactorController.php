<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\User\VerifyTwoFactorDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorController extends ApiController
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorAuthService
    ) {}

    public function generate(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $secret = $this->twoFactorAuthService->generateSecret();

        $qrCodeUrl = $this->twoFactorAuthService->getQrCodeUrl(
            config('app.name'),
            (string) $user?->email,
            $secret,
        );

        $request->session()->put('2fa_secret', $secret);
        $request->session()->put('2fa_qr', $qrCodeUrl);

        return back();
    }

    public function enable(Request $request, VerifyTwoFactorDTO $dto): RedirectResponse
    {
        $secret = $request->session()->get('2fa_secret');

        if (!$secret) {
            return back()->withErrors(['otp' => 'Session expired. Please generate a new QR code.']);
        }

        $user = auth()->user();
        assert($user instanceof User);

        $codes = $this->twoFactorAuthService->enable2fa($user, $secret, $dto->otp);

        if ($codes) {
            $request->session()->forget(['2fa_secret', '2fa_qr']);

            return back()
                ->with('success', 'Two factor authentication successfully enabled.')
                ->with('recovery_codes', $codes);
        }

        return back()->withErrors(['otp' => 'Invalid verification code. Please try again.']);
    }

    public function disable(): RedirectResponse
    {
        $user = auth()->user();
        assert($user instanceof User);

        $this->twoFactorAuthService->disable2fa($user);

        return back()->with('success', 'Two factor authentication successfully disabled.');
    }

    public function showVerifyForm(Request $request): RedirectResponse|View
    {
        if (!$request->session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.2fa');
    }

    public function verifyLogin(Request $request, VerifyTwoFactorDTO $dto): RedirectResponse
    {
        $userId = $request->session()->get('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        if ($this->twoFactorAuthService->verifyLogin((int) $userId, $dto->otp)) {
            $request->session()->forget('2fa:user_id');
            return redirect()->intended('/profile');
        }

        return back()->withErrors(['otp' => 'Invalid verification code. Please try again.']);
    }
}
