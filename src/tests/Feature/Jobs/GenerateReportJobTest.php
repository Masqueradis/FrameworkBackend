<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\OrderStatus;
use App\Enums\ReportStatus;
use App\Jobs\GenerateReportJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GenerateReportJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws \Throwable
     */
    #[Test]
    public function testGeneratesSalesCsvAndUploadsToMinio(): void
    {
        Storage::fake('minio');

        $admin = User::factory()->create();
        $report = Report::create([
            'admin_id' => $admin->id,
            'type' => 'sales',
            'filters' => [
                'date_from' => now()->subDay(5)->toDateString(),
                'date_to' => now()->addDays(5)->toDateString(),
            ],
            'status' => ReportStatus::Pending,
        ]);

        Order::create([
            'customer_id' => $admin->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123',
            'total_amount_cents' => 1500,
            'status' => OrderStatus::Completed,
            'created_at' => now(),
        ]);

        $job = new GenerateReportJob($report->id);
        app()->call([$job, 'handle']);

        $report->refresh();

        $this->assertEquals(ReportStatus::Completed, $report->status);
        $this->assertNotNull($report->file_path);

        Storage::disk('minio')->assertExists($report->file_path);

        $csvContent = Storage::disk('minio')->get($report->file_path);
        $this->assertStringContainsString('test@example', $csvContent);
        $this->assertStringContainsString('1500', $csvContent);
    }

    #[Test]
    public function testUpdatesStatusToFailedOnException(): void
    {
        $admin = User::factory()->create();
        $report = Report::create([
            'admin_id' => $admin->id,
            'type' => 'invalid_type',
            'filters' => [],
            'status' => ReportStatus::Pending,
        ]);

        $job = new GenerateReportJob($report->id);

        try {
            app()->call([$job, 'handle']);
        } catch (\Throwable $e) {
        }

        $this->assertEquals(ReportStatus::Failed, $report->fresh()->status);
    }

    #[Test]
    public function testGeneratesInventoryCsvAndUploadsToMinio(): void
    {
        $admin = User::factory()->create();
        Storage::fake('minio');

        $report = Report::create([
            'admin_id' => $admin->id,
            'type' => 'inventory',
            'filters' => [],
            'status' => ReportStatus::Pending,
            ]);

        Product::factory()->count(3)->create();

        $job = new GenerateReportJob($report->id);
        $job->handle(app(OrderRepositoryInterface::class), app(ProductRepository::class));

        $report->refresh();

        $this->assertEquals(ReportStatus::Completed, $report->status);
        $this->assertNotNull($report->file_path);
        Storage::disk('minio')->assertExists($report->file_path);
    }
}
