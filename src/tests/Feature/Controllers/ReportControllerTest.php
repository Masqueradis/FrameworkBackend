<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Enums\ReportStatus;
use App\Models\Permission;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();

        Role::firstOrCreate(['name' => 'admin']);
        Permission::firstOrCreate(['name' => 'access-panel']);

        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('access-panel');
    }

    #[Test]
    public function test_admin_can_request_report_generation(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)->post(route('admin.reports.store'), [
            'type' => 'sales',
            'date_from' => now()->subMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Report sent to generation queue.');

        $this->assertDatabaseHas('reports', [
            'admin_id' => $this->admin->id,
            'type' => 'sales',
            'status' => ReportStatus::Pending,
        ]);
    }

    #[Test]
    public function test_admin_can_view_reports_list(): void
    {
        Report::create([
            'admin_id' => $this->admin->id,
            'type' => 'sales',
            'filters' => [],
            'status' => ReportStatus::Completed,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.index');
        $response->assertViewHas('reports');
    }

    #[Test]
    public function test_admin_can_download_completed_report(): void
    {
        Storage::fake('minio');
        $filePath = 'reports/test_report.csv';
        Storage::disk('minio')->put($filePath, 'dummy csv content');

        $report = Report::create([
            'admin_id' => $this->admin->id,
            'type' => 'sales',
            'filters' => [],
            'status' => ReportStatus::Completed,
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.download', $report));

        $response->assertOk();
        $response->assertDownload('test_report.csv');
    }

    #[Test]
    public function test_admin_cannot_download_pending_report(): void
    {
        $this->withoutExceptionHandling();

        $report = Report::create([
            'admin_id' => $this->admin->id,
            'type' => 'sales',
            'filters' => [],
            'status' => ReportStatus::Pending,
        ]);

        $this->expectException(NotFoundHttpException::class);
        $this->actingAs($this->admin)->get(route('admin.reports.download', $report));
    }
}
