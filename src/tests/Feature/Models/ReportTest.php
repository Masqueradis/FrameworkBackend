<?php

namespace Tests\Feature\Models;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_report_belongs_to_admin(): void
    {
        $admin = User::factory()->create();
        $report = Report::create([
            'admin_id' => $admin->id,
            'type' => 'sales',
            'filters' => [],
            'status' => ReportStatus::Completed,
        ]);

        $this->assertInstanceOf(BelongsTo::class, $report->admin());
        $this->assertEquals($admin->id, $report->admin->id);
    }
}
