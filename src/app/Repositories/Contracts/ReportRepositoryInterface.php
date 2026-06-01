<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;

interface ReportRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     * @return Report
     */
    public function create(array $data): Report;

    /**
     * @param int $adminId
     * @return Collection<int, Report>
     */
    public function getByAdmin(int $adminId): Collection;
    public function updateStatus(Report $report, ReportStatus $status): bool;
}
