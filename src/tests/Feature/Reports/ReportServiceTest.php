<?php

namespace Tests\Feature\Reports;

use App\DTO\Report\RequestReportDTO;
use App\Enums\ReportStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

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
