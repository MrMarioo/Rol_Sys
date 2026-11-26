<?php

namespace Tests\Unit;

use App\Models\Analytics;
use App\Models\Field;
use App\Models\Report;
use App\Notifications\AnalysisCompleted;
use App\Notifications\AnalysisFailed;
use App\Notifications\ReportGenerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_analysis_completed_payload(): void
    {
        $field = Field::factory()->create(['name' => 'North field']);
        $analytics = Analytics::factory()->for($field)->create();

        $notification = new AnalysisCompleted($analytics);

        $this->assertSame(['database'], $notification->via($field->user));
        $this->assertSame([
            'title' => 'AI analysis completed',
            'message' => 'Analysis of field North field is ready.',
            'level' => 'success',
            'url' => '/analytics/'.$analytics->id,
        ], $notification->toArray($field->user));
    }

    public function test_analysis_failed_payload(): void
    {
        $field = Field::factory()->create(['name' => 'South field']);

        $notification = new AnalysisFailed($field);

        $this->assertSame(['database'], $notification->via($field->user));
        $this->assertSame([
            'title' => 'AI analysis failed',
            'message' => 'Field South field could not be analysed. Please try again later.',
            'level' => 'danger',
            'url' => '/fields/'.$field->id.'/analytics',
        ], $notification->toArray($field->user));
    }

    public function test_report_generated_payload(): void
    {
        $report = Report::factory()->create(['title' => 'Week 47']);

        $notification = new ReportGenerated($report);

        $this->assertSame(['database'], $notification->via($report->user));
        $this->assertSame([
            'title' => 'Report generated',
            'message' => 'Report "Week 47" is ready to download.',
            'level' => 'success',
            'url' => '/reports/'.$report->id,
        ], $notification->toArray($report->user));
    }
}
