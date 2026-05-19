<?php

declare (strict_types=1);

namespace App\Services;

use App\DTO\Report\RequestReportDTO;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;

class ReportService
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository,
    ) {}

    public function requestGeneration(int $adminId, RequestReportDTO $dto): Report
    {
        $report = $this->reportRepository->create([
            'admin_id' => $adminId,
            'type' => $dto->type,
            'filters' => [
                'date_from' => $dto->date_from,
                'date_to' => $dto->date_to,
            ],
            'status' => ReportStatus::Pending,
        ]);

        GenerateReportJob::dispatch($report->id);

        return $report;
    }
}
