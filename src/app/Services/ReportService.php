<?php

declare (strict_types=1);

namespace App\Services;

use App\DTO\Report\RequestReportDTO;
use App\Enums\ReportStatus;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

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

    public function getDownloadPath(Report $report, int $adminId): string
    {
        if ($report->admin_id !== $adminId) {
            abort(Response::HTTP_FORBIDDEN, 'You have no access to this report.');
        }

        /** @var ReportStatus $status */
        $status = $report->status;

        if($status !== ReportStatus::Completed || !$report->file_path) {
            abort(Response::HTTP_NOT_FOUND, 'Report not found.');
        }

        return $report->file_path;
    }
}
