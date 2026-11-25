<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\Field;
use App\Services\FieldAnalyticsSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FieldAnalyticsSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_field_without_analyses_returns_empty_summary(): void
    {
        $summary = $this->summarize(Field::factory()->create());

        $this->assertNull($summary['latestAnalysis']);
        $this->assertSame([], $summary['topRecommendations']);
        $this->assertSame([], $summary['analysisHistory']);
    }

    public function test_recommendations_sorted_by_priority_then_urgency(): void
    {
        $field = Field::factory()->create();
        Analytics::factory()->create([
            'field_id' => $field->id,
            'results' => [],
            'recommendations' => [
                ['priority' => 'low', 'action' => 'low'],
                ['priority' => 'high', 'action' => 'high7', 'urgency_days' => 7],
                ['priority' => 'medium', 'action' => 'medium'],
                ['priority' => 'high', 'action' => 'high3', 'urgency_days' => 3],
                ['action' => 'none'],
            ],
        ]);

        $actions = array_column($this->summarize($field)['latestAnalysis']['recommendations'], 'action');

        $this->assertSame(['high3', 'high7', 'medium', 'low', 'none'], $actions);
    }

    public function test_top_recommendations_are_first_three(): void
    {
        $field = Field::factory()->create();
        Analytics::factory()->create([
            'field_id' => $field->id,
            'results' => [],
            'recommendations' => [
                ['priority' => 'low', 'action' => 'a'],
                ['priority' => 'high', 'action' => 'b'],
                ['priority' => 'medium', 'action' => 'c'],
                ['priority' => 'high', 'action' => 'd', 'urgency_days' => 1],
            ],
        ]);

        $summary = $this->summarize($field);

        $this->assertSame(['d', 'b', 'c'], array_column($summary['topRecommendations'], 'action'));
    }

    public function test_error_and_null_sub_results_become_null(): void
    {
        $field = Field::factory()->create();
        Analytics::factory()->create([
            'field_id' => $field->id,
            'results' => [
                'vegetation_health' => ['error' => 'x'],
                'soil_moisture' => null,
            ],
            'recommendations' => [],
        ]);

        $latest = $this->summarize($field)['latestAnalysis'];

        $this->assertNull($latest['vegetation']);
        $this->assertNull($latest['moisture']);
        $this->assertNull($latest['health_score']);
    }

    public function test_growth_prediction_error_gives_null_health_score(): void
    {
        $field = Field::factory()->create();
        Analytics::factory()->create([
            'field_id' => $field->id,
            'results' => ['growth_prediction' => ['error' => 'x', 'current_status' => ['health_score' => 80]]],
            'recommendations' => [],
        ]);

        $this->assertNull($this->summarize($field)['latestAnalysis']['health_score']);
    }

    public function test_moisture_scaled_to_percent(): void
    {
        $field = Field::factory()->create();
        Analytics::factory()->create([
            'field_id' => $field->id,
            'results' => [
                'vegetation_health' => ['avg_ndvi' => 0.7234, 'health_status' => 'Good'],
                'soil_moisture' => ['avg_moisture' => 0.275, 'moisture_status' => 'Low'],
                'growth_prediction' => ['current_status' => ['health_score' => 82]],
                'anomalies' => [['type' => 'ndvi_anomaly']],
            ],
            'recommendations' => [],
        ]);

        $latest = $this->summarize($field)['latestAnalysis'];

        $this->assertSame(['status' => 'Good', 'ndvi' => 0.72], $latest['vegetation']);
        $this->assertSame(['status' => 'Low', 'percent' => 27.5], $latest['moisture']);
        $this->assertSame(82, $latest['health_score']);
        $this->assertSame(1, $latest['anomaly_count']);
    }

    public function test_non_array_recommendations_are_ignored(): void
    {
        $field = Field::factory()->create();
        DB::table('analytics')->insert([
            'field_id' => $field->id,
            'analysis_type' => 'standard_analysis',
            'analysis_date' => '2026-01-01',
            'results' => json_encode([]),
            'recommendations' => json_encode('Zalecamy nawadnianie'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $summary = $this->summarize($field);

        $this->assertSame([], $summary['latestAnalysis']['recommendations']);
        $this->assertSame(0, $summary['analysisHistory'][0]['recommendation_count']);
    }

    public function test_history_is_newest_first_and_latest_is_first_item(): void
    {
        $field = Field::factory()->create();
        Analytics::factory()->create(['field_id' => $field->id, 'analysis_date' => '2026-01-01']);
        Analytics::factory()->withPrediction()->create(['field_id' => $field->id, 'analysis_date' => '2026-01-05']);
        $newest = Analytics::factory()->create(['field_id' => $field->id, 'analysis_date' => '2026-01-05']);

        $summary = $this->summarize($field);
        $history = $summary['analysisHistory'];

        $this->assertSame(['2026-01-05', '2026-01-05', '2026-01-01'], array_column($history, 'date'));
        $this->assertSame($newest->id, $history[0]['id']);
        $this->assertSame($history[0]['id'], $summary['latestAnalysis']['id']);
        $this->assertSame('With prediction', $history[1]['type_label']);
        $this->assertSame('Standard', $history[0]['type_label']);
    }

    private function summarize(Field $field): array
    {
        return app(FieldAnalyticsSummaryService::class)->summarize(field: $field);
    }
}
