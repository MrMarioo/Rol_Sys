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
        $field = Field::factory()->create(['name' => 'Pole A']);
        $analytics = Analytics::factory()->for($field)->create();

        $notification = new AnalysisCompleted($analytics);

        $this->assertSame(['database'], $notification->via($field->user));
        $this->assertSame([
            'title' => 'Analiza AI zakończona',
            'message' => 'Analiza pola Pole A jest gotowa.',
            'level' => 'success',
            'url' => '/analytics/' . $analytics->id,
        ], $notification->toArray($field->user));
    }

    public function test_analysis_failed_payload(): void
    {
        $field = Field::factory()->create(['name' => 'Pole B']);

        $notification = new AnalysisFailed($field);

        $this->assertSame(['database'], $notification->via($field->user));
        $this->assertSame([
            'title' => 'Analiza AI nie powiodła się',
            'message' => 'Nie udało się przeanalizować pola Pole B. Spróbuj ponownie później.',
            'level' => 'danger',
            'url' => '/fields/' . $field->id . '/analytics',
        ], $notification->toArray($field->user));
    }

    public function test_report_generated_payload(): void
    {
        $report = Report::factory()->create(['title' => 'Tydzień 47']);

        $notification = new ReportGenerated($report);

        $this->assertSame(['database'], $notification->via($report->user));
        $this->assertSame([
            'title' => 'Raport wygenerowany',
            'message' => 'Raport „Tydzień 47” jest gotowy do pobrania.',
            'level' => 'success',
            'url' => '/reports/' . $report->id,
        ], $notification->toArray($report->user));
    }
}
