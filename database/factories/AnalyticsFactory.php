<?php

namespace Database\Factories;

use App\Models\Analytics;
use App\Models\Field;
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
            'results' => [],
        ];
    }
}
