<?php

namespace Database\Seeders;

use App\Models\Analytics;
use App\Models\Field;
use App\Models\FieldData;
use Carbon\CarbonImmutable;
use Database\Factories\Support\AnalysisResultsBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AnalyticsSeeder extends Seeder
{
    private const WINDOW_DAYS = 14;

    private const PREDICTION_DAYS = 7;

    public function run(): void
    {
        $created = 0;

        foreach (Field::query()->orderBy('id')->get() as $field) {
            $created += $this->seedField(field: $field);
        }

        mt_srand();

        $this->command?->info(sprintf('Created %d analyses', $created));
    }

    private function seedField(Field $field): int
    {
        Analytics::query()->where('field_id', $field->id)->delete();

        $readings = FieldData::query()
            ->where('field_id', $field->id)
            ->whereIn('data_type', ['ndvi', 'soil_moisture'])
            ->get(['data_type', 'collection_date', 'data']);

        if ($readings->isEmpty()) {
            return 0;
        }

        mt_srand($field->id);

        $oldest = CarbonImmutable::parse($readings->min('collection_date'));
        $newest = CarbonImmutable::parse($readings->max('collection_date'));
        $count = mt_rand(3, 6);
        $span = $oldest->diffInDays($newest);

        for ($index = 0; $index < $count; $index++) {
            $date = $oldest->addDays((int) round($span * ($index + 1) / $count));

            $this->createAnalysis(field: $field, readings: $readings, date: $date, withPrediction: $index % 2 === 1);
        }

        return $count;
    }

    private function createAnalysis(Field $field, Collection $readings, CarbonImmutable $date, bool $withPrediction): void
    {
        $from = $date->subDays(self::WINDOW_DAYS - 1);

        $window = $readings->filter(
            fn (FieldData $row): bool => $row->collection_date->between($from->startOfDay(), $date->endOfDay()),
        );

        $results = AnalysisResultsBuilder::build(
            ndvi: $this->stats(window: $window, type: 'ndvi', key: 'avg_ndvi'),
            moisture: $this->stats(window: $window, type: 'soil_moisture', key: 'avg_moisture'),
            date: $date->toDateString(),
            withPrediction: $withPrediction,
            predictionDays: self::PREDICTION_DAYS,
        );

        Analytics::query()->create([
            'field_id' => $field->id,
            'analysis_type' => $withPrediction ? 'comprehensive_with_prediction' : 'standard_analysis',
            'analysis_date' => $date->toDateString(),
            'results' => $results,
            'recommendations' => $results['recommendations'],
            'parameters' => [
                'sensitivity' => 'medium',
                'data_count' => $window->count(),
                'include_prediction' => $withPrediction,
                'prediction_days' => self::PREDICTION_DAYS,
                'date_range' => [
                    'from' => $from->toDateString(),
                    'to' => $date->toDateString(),
                ],
            ],
        ]);
    }

    private function stats(Collection $window, string $type, string $key): ?array
    {
        $values = $window
            ->where('data_type', $type)
            ->map(fn (FieldData $row): ?float => isset($row->data[$key]) ? (float) $row->data[$key] : null)
            ->filter(fn (?float $value): bool => $value !== null);

        if ($values->isEmpty()) {
            return null;
        }

        return [
            'avg' => $values->avg(),
            'min' => $values->min(),
            'max' => $values->max(),
        ];
    }
}
