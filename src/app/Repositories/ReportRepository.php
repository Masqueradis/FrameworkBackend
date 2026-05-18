<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Collection;

class ReportRepository implements ReportRepositoryInterface
{
    public function create(array $data): Report
    {
        return Report::create($data);
    }

    public function getByAdmin(int $adminId): Collection
    {
        return Report::where('admin_id', $adminId)
            ->latest()
            ->get();
    }

    public function updateStatus(Report $report, ReportStatus $status): bool
    {
        return $report->update(['status' => $status]);
    }
}
