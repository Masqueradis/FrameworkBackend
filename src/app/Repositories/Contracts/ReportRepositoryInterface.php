<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Support\Collection;

interface ReportRepositoryInterface
{
    public function create(array $data): Report;
    public function getByAdmin(int $adminId): Collection;
    public function updateStatus(Report $report, ReportStatus $status): bool;
}
