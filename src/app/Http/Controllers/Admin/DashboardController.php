<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends ApiController
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        assert($user instanceof User);

        $stats = $this->dashboardService->getStatsForDashboard($user);

        return view('admin.dashboard', $stats);
    }
}
