<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Analytics;
use App\Models\Field;
use App\Models\FieldData;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function ndviReading(Field $field, float $value, string $date): FieldData
    {
        return FieldData::factory()->create([
            'field_id' => $field->id,
            'data_type' => 'ndvi',
            'collection_date' => $date,
            'data' => ['ndvi_values' => array_fill(0, 100, $value)],
        ]);
    }

    public function test_store_rejects_fields_of_other_users(): void
    {
        $user = User::factory()->create();
        $foreignField = Field::factory()->create();

        $this->actingAs($user)
            ->post(route('reports.store'), [
                'title' => 'Spy report',
                'type' => 'weekly_summary',
                'fields_included' => [$foreignField->id],
                'parameters' => ['start_date' => '2026-01-01'],
            ])
            ->assertSessionHasErrors('fields_included.0');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_store_creates_draft_and_ignores_client_content(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('reports.store'), [
                'title' => 'Weekly',
                'type' => 'weekly_summary',
                'fields_included' => [$field->id],
                'parameters' => ['start_date' => '2026-01-01'],
                'content' => ['<img src="http://ai-service:5000/">'],
            ])
            ->assertRedirect();

        $report = Report::firstOrFail();
        $this->assertSame(ReportStatus::Draft, $report->status);
        $this->assertSame([], $report->content ?? []);
        $this->assertSame($user->id, $report->user_id);
    }

    public function test_generate_skips_fields_the_owner_does_not_own(): void
    {
        $user = User::factory()->create();
        $ownField = Field::factory()->for($user)->create(['name' => 'Own field']);
        $foreignField = Field::factory()->create(['name' => 'Foreign field']);
        $report = Report::factory()->for($user)->create([
            'type' => 'weekly_summary',
            'fields_included' => [$ownField->id, $foreignField->id],
            'parameters' => ['start_date' => now()->subWeek()->toDateString(), 'end_date' => now()->toDateString()],
        ]);

        $this->actingAs($user)->post(route('reports.generate', $report))->assertSessionHas('success');

        $summary = $report->fresh()->content['summary'];
        $this->assertSame(['Own field'], array_values(array_column($summary, 'field_name')));
    }

    public function test_comparative_fields_report_contains_real_metrics(): void
    {
        $user = User::factory()->create();
        $healthy = Field::factory()->for($user)->create(['name' => 'Healthy']);
        $weak = Field::factory()->for($user)->create(['name' => 'Weak']);
        $this->ndviReading($healthy, 0.8, now()->subDays(3)->toDateString());
        $this->ndviReading($weak, 0.2, now()->subDays(3)->toDateString());
        Analytics::factory()->create(['field_id' => $weak->id, 'analysis_date' => now()->subDays(2)]);
        $report = Report::factory()->for($user)->create([
            'type' => 'comparative',
            'fields_included' => [$healthy->id, $weak->id],
            'parameters' => ['comparison_type' => 'fields'],
        ]);

        $this->actingAs($user)->post(route('reports.generate', $report));

        $fields = collect($report->fresh()->content['fields'])->keyBy('field_name');
        $this->assertSame(0.8, $fields['Healthy']['avg_ndvi']);
        $this->assertSame('excellent', $fields['Healthy']['status']);
        $this->assertSame('poor', $fields['Weak']['status']);
        $this->assertSame(1, $fields['Weak']['data_collections']);
    }

    public function test_comparative_time_periods_report_compares_current_and_previous(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        $this->ndviReading($field, 0.6, now()->subDays(5)->toDateString());
        $this->ndviReading($field, 0.4, now()->subDays(40)->toDateString());
        $report = Report::factory()->for($user)->create([
            'type' => 'comparative',
            'fields_included' => [$field->id],
            'parameters' => ['comparison_type' => 'time_periods'],
        ]);

        $this->actingAs($user)->post(route('reports.generate', $report));

        $periods = $report->fresh()->content['periods'];
        $this->assertSame(0.6, $periods['current']['avg_ndvi']);
        $this->assertSame(0.4, $periods['previous']['avg_ndvi']);
    }

    public function test_download_returns_pdf_with_escaped_content(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create([
            'title' => 'Escaped',
            'status' => 'generated',
            'content' => ['note' => '<img src="http://ai-service:5000/">'],
        ]);

        $response = $this->actingAs($user)->get(route('reports.download', $report));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertFalse(config('dompdf.options.enable_remote'));

        $html = view('reports-pdf', ['report' => $report])->render();
        $this->assertStringContainsString('&lt;img src=&quot;http://ai-service:5000/&quot;&gt;', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_other_user_cannot_generate_or_download_report(): void
    {
        $report = Report::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->post(route('reports.generate', $report))->assertForbidden();
        $this->actingAs($intruder)->get(route('reports.download', $report))->assertForbidden();
    }
}
