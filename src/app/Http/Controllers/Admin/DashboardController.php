<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
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
