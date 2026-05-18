<?php

namespace Tests\Feature\Repositories;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use App\Repositories\ReportRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ReportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ReportRepository();
    }

    #[Test]
    public function testCanCreateAReport(): void
    {
        $admin = User::factory()->create();

        $report = $this->repository->create([
            'admin_id' => $admin->id,
            'type' => 'sales',
            'filters' => [
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-02',
            ],
            'status' => ReportStatus::Pending->value,
        ]);

        $this->assertInstanceOf(Report::class, $report);
        $this->assertEquals('sales', $report->type);
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'type' => 'sales']);
    }

    #[Test]
    public function testCanGetReportsForAdmin(): void
    {
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();

        Report::factory()->count(3)->create(['admin_id' => $admin->id]);
        Report::factory()->count(2)->create(['admin_id' => $otherAdmin->id]);

        $reports = $this->repository->getByAdmin($admin->id);

        $this->assertCount(3, $reports);
        $this->assertEquals($admin->id, $reports->first()->admin_id);
    }
}
