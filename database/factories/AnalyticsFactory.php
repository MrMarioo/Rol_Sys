<?php

namespace Database\Factories;

use App\Models\Analytics;
use App\Models\Field;
use Carbon\CarbonImmutable;
use Database\Factories\Support\AnalysisResultsBuilder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Analytics>
 */
class AnalyticsFactory extends Factory
{
    protected $model = Analytics::class;

    public function definition(): array
    {
        return [
            'field_id' => Field::factory(),
            'analysis_type' => 'standard_analysis',
            'analysis_date' => now()->toDateString(),
            'results' => fn (array $attributes): array => AnalysisResultsBuilder::build(
                ndvi: ['avg' => 0.72, 'min' => 0.55, 'max' => 0.85],
                moisture: ['avg' => 0.45, 'min' => 0.30, 'max' => 0.60],
                date: $this->date(attributes: $attributes),
                withPrediction: $this->includesPrediction(attributes: $attributes),
            ),
            'recommendations' => fn (array $attributes): ?array => $attributes['results']['recommendations'] ?? null,
            'parameters' => fn (array $attributes): array => [
                'sensitivity' => 'medium',
                'data_count' => 180,
                'include_prediction' => $this->includesPrediction(attributes: $attributes),
                'prediction_days' => 7,
                'date_range' => [
                    'from' => CarbonImmutable::parse($this->date(attributes: $attributes))->subDays(13)->toDateString(),
                    'to' => $this->date(attributes: $attributes),
                ],
            ],
        ];
    }

    public function withPrediction(): static
    {
        return $this->state(fn (): array => [
            'analysis_type' => 'comprehensive_with_prediction',
        ]);
    }

    private function date(array $attributes): string
    {
        return CarbonImmutable::parse($attributes['analysis_date'])->toDateString();
    }

    private function includesPrediction(array $attributes): bool
    {
        return $attributes['analysis_type'] === 'comprehensive_with_prediction';
    }
}
