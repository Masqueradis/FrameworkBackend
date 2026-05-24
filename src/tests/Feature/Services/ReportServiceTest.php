<?php

namespace Tests\Feature\Services;

use App\DTO\Report\RequestReportDTO;
use App\Enums\ReportStatus;
use App\Jobs\GenerateReportJob;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testRequestGenerationCreatesRecordAndDispatchesJob(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $service = app(ReportService::class);
        $dto = new RequestReportDTO('sales', '2026-01-01', '2026-01-31');

        $report = $service->requestGeneration($admin->id, $dto);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'admin_id' => $admin->id,
            'type' => 'sales',
            'status' => ReportStatus::Pending,
        ]);

        Queue::assertPushed(GenerateReportJob::class, function (GenerateReportJob $job) use ($report) {
            return $job->reportId === $report->id;
        });
    }
}
