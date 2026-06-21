<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTO\Report\RequestReportDTO;
use App\Http\Controllers\ApiController;
use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends ApiController
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ReportRepositoryInterface $reportRepository,
    ) {}

    public function index(): View
    {
        $reports = $this->reportRepository->getByAdmin((int) auth()->id());

        return view('admin.reports.index', compact('reports'));
    }

    public function store(RequestReportDTO $dto): RedirectResponse
    {
        $this->reportService->requestGeneration((int) auth()->id(), $dto);

        return back()->with('success', 'Report sent to generation queue.');
    }

    public function download(Report $report): StreamedResponse
    {
        $filePath = $this->reportService->getDownloadPath($report, (int) auth()->id());

        return Storage::disk('minio')->download($filePath);
    }
}
