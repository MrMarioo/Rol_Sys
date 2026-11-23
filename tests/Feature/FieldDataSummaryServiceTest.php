<?php

namespace Tests\Feature;

use App\Models\Field;
use App\Models\FieldData;
use App\Services\FieldDataSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldDataSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_soil_moisture_is_shown_as_percent(): void
    {
        $field = Field::factory()->create();
        $this->record($field, 'soil_moisture', ['avg_moisture' => 0.312], '2026-01-01');

        $summary = $this->summarize($field);

        $this->assertSame('soil_moisture', $summary['latestReadings'][0]['type']);
        $this->assertSame('%', $summary['latestReadings'][0]['unit']);
        $this->assertSame('31.2', $summary['latestReadings'][0]['display']);
        $this->assertNull($summary['latestReadings'][0]['trend']);
        $this->assertSame(31.2, $summary['trendSeries']['soil_moisture']['points'][0]['value']);
    }

    public function test_trend_compares_latest_with_previous_reading(): void
    {
        $rising = Field::factory()->create();
        $this->record($rising, 'ndvi', ['avg_ndvi' => 0.5], '2026-01-01');
        $this->record($rising, 'ndvi', ['avg_ndvi' => 0.7], '2026-01-02');

        $flat = Field::factory()->create();
        $this->record($flat, 'ndvi', ['avg_ndvi' => 0.7], '2026-01-01');
        $this->record($flat, 'ndvi', ['avg_ndvi' => 0.7], '2026-01-02');

        $risingReading = $this->summarize($rising)['latestReadings'][0];
        $this->assertSame('up', $risingReading['trend']);
        $this->assertSame('0.700', $risingReading['display']);
        $this->assertSame('flat', $this->summarize($flat)['latestReadings'][0]['trend']);
    }

    public function test_invalid_records_are_skipped_but_listed_as_recent(): void
    {
        $field = Field::factory()->create();
        $this->record($field, 'ndvi', ['avg_ndvi' => 0.6], '2026-01-01');
        $this->record($field, 'ndvi', ['foo' => 1], '2026-01-02');
        $this->record($field, 'ndvi', ['avg_ndvi' => 'abc'], '2026-01-03');

        $summary = $this->summarize($field);

        $this->assertSame('0.600', $summary['latestReadings'][0]['display']);
        $this->assertSame('2026-01-01', $summary['latestReadings'][0]['date']);
        $this->assertNull($summary['latestReadings'][0]['trend']);
        $this->assertCount(1, $summary['trendSeries']['ndvi']['points']);
        $this->assertCount(3, $summary['recentReadings']);
        $this->assertSame('—', $summary['recentReadings'][0]['display']);
        $this->assertSame('—', $summary['recentReadings'][1]['display']);
    }

    public function test_unknown_type_appears_only_in_recent_readings(): void
    {
        $field = Field::factory()->create();
        $this->record($field, 'custom_sensor', ['value' => 3], '2026-01-01');

        $summary = $this->summarize($field);

        $this->assertSame([], $summary['latestReadings']);
        $this->assertSame([], $summary['trendSeries']);
        $this->assertSame('Custom Sensor', $summary['recentReadings'][0]['label']);
        $this->assertSame('—', $summary['recentReadings'][0]['display']);
    }

    public function test_series_window_is_anchored_to_newest_record(): void
    {
        $field = Field::factory()->create();
        $this->record($field, 'ndvi', ['avg_ndvi' => 0.4], '2025-01-10');
        $this->record($field, 'ndvi', ['avg_ndvi' => 0.5], '2025-01-11');
        $this->record($field, 'ndvi', ['avg_ndvi' => 0.6], '2025-04-11');

        $points = $this->summarize($field)['trendSeries']['ndvi']['points'];

        $this->assertSame(['2025-01-11', '2025-04-11'], array_column($points, 'date'));
    }

    public function test_same_date_values_are_averaged(): void
    {
        $field = Field::factory()->create();
        $this->record($field, 'ndvi', ['avg_ndvi' => 0.4], '2025-05-01');
        $this->record($field, 'ndvi', ['avg_ndvi' => 0.6], '2025-05-01');

        $points = $this->summarize($field)['trendSeries']['ndvi']['points'];

        $this->assertCount(1, $points);
        $this->assertSame(0.5, $points[0]['value']);
    }

    public function test_text_kinds_have_display_but_no_series(): void
    {
        $field = Field::factory()->create();
        $this->record($field, 'growth_stage', ['growth_stage' => 'flowering', 'bbch_code' => 61], '2026-01-01');
        $this->record($field, 'fertilizer_application', ['fertilizer_type' => 'nitrogen', 'amount_per_hectare' => 120], '2026-01-01');

        $summary = $this->summarize($field);
        $readings = collect($summary['latestReadings'])->keyBy('type');

        $this->assertSame('flowering (BBCH 61)', $readings['growth_stage']['display']);
        $this->assertSame('text', $readings['growth_stage']['kind']);
        $this->assertSame('nitrogen 120 kg/ha', $readings['fertilizer_application']['display']);
        $this->assertSame([], $summary['trendSeries']);
    }

    public function test_non_scalar_bbch_code_is_ignored(): void
    {
        $field = Field::factory()->create();
        $this->record($field, 'growth_stage', ['growth_stage' => 'flowering', 'bbch_code' => [61]], '2026-01-01');

        $this->assertSame('flowering', $this->summarize($field)['latestReadings'][0]['display']);
    }

    public function test_infinite_numeric_value_is_skipped(): void
    {
        $field = Field::factory()->create();
        $this->record($field, 'ndvi', ['avg_ndvi' => '1e400'], '2026-01-01');

        $summary = $this->summarize($field);

        $this->assertSame([], $summary['latestReadings']);
        $this->assertSame('—', $summary['recentReadings'][0]['display']);
        $this->assertNotFalse(json_encode($summary));
    }

    public function test_recent_readings_are_limited_to_five_newest(): void
    {
        $field = Field::factory()->create();
        foreach (range(1, 7) as $day) {
            $this->record($field, 'ndvi', ['avg_ndvi' => 0.5], sprintf('2026-01-%02d', $day));
        }

        $recent = $this->summarize($field)['recentReadings'];

        $this->assertCount(5, $recent);
        $this->assertSame('2026-01-07', $recent[0]['date']);
    }

    private function record(Field $field, string $type, array $data, string $date): void
    {
        FieldData::factory()->create([
            'field_id' => $field->id,
            'data_type' => $type,
            'data' => $data,
            'collection_date' => $date,
        ]);
    }

    private function summarize(Field $field): array
    {
        return app(FieldDataSummaryService::class)->summarize(field: $field);
    }
}
