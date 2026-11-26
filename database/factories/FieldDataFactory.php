<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\FieldData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldData>
 */
final class FieldDataFactory extends Factory
{
    protected $model = FieldData::class;

    public function definition(): array
    {
        return [
            'field_id' => Field::factory(),
            'collection_date' => now()->toDateString(),
            'data_type' => 'ndvi',
            'data' => ['avg_ndvi' => 0.5],
        ];
    }
}
