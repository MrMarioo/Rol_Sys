<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\Field;
use App\Models\FieldData;
use Carbon\CarbonImmutable;
use Database\Seeders\AnalyticsSeeder;
use Database\Seeders\FieldDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_field_with_readings_gets_three_to_six_analyses(): void
    {
        $fields = Field::factory()->count(3)->create();

        $this->seed([FieldDataSeeder::class, AnalyticsSeeder::class]);

        foreach ($fields as $field) {
            $count = Analytics::where('field_id', $field->id)->count();

            $this->assertGreaterThanOrEqual(3, $count);
            $this->assertLessThanOrEqual(6, $count);
        }
    }

    public function test_recommendations_are_non_empty_arrays(): void
    {
        Field::factory()->count(2)->create();

        $this->seed([FieldDataSeeder::class, AnalyticsSeeder::class]);

        $this->assertGreaterThan(0, Analytics::count());

        foreach (Analytics::all() as $analytics) {
            $this->assertIsArray($analytics->recommendations);
            $this->assertNotEmpty($analytics->recommendations);
        }
    }

    public function test_avg_ndvi_matches_readings_in_window(): void
    {
        $field = Field::factory()->create();

        $this->seed([FieldDataSeeder::class, AnalyticsSeeder::class]);

        $analytics = Analytics::where('field_id', $field->id)->orderBy('analysis_date')->get()->last();
        $date = CarbonImmutable::parse($analytics->analysis_date);

        $expected = FieldData::where('field_id', $field->id)
            ->where('data_type', 'ndvi')
            ->whereBetween('collection_date', [$date->subDays(13)->toDateString(), $date->toDateString()])
            ->get()
            ->avg(fn (FieldData $row): float => $row->data['avg_ndvi']);

        $this->assertEqualsWithDelta($expected, $analytics->results['vegetation_health']['avg_ndvi'], 0.001);
        $this->assertSame($date->subDays(13)->toDateString(), $analytics->parameters['date_range']['from']);
    }

    public function test_every_other_analysis_includes_prediction(): void
    {
        $field = Field::factory()->create();

        $this->seed([FieldDataSeeder::class, AnalyticsSeeder::class]);

        $types = Analytics::where('field_id', $field->id)->orderBy('analysis_date')->pluck('analysis_type')->all();

        foreach ($types as $index => $type) {
            $this->assertSame($index % 2 === 1, $type === 'comprehensive_with_prediction');
        }
    }

    public function test_field_without_readings_gets_no_analyses(): void
    {
        $field = Field::factory()->create();

        $this->seed(AnalyticsSeeder::class);

        $this->assertSame(0, Analytics::where('field_id', $field->id)->count());
    }
}
