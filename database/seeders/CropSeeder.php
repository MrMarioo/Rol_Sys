<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;

final class CropSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            [
                'name' => 'Wheat',
                'description' => 'Common cereal crop.',
                'optimal_conditions' => [
                    'temperature_min' => 4,
                    'temperature_max' => 30,
                    'humidity_min' => 40,
                    'humidity_max' => 70,
                    'ph_min' => 6.0,
                    'ph_max' => 7.5,
                ],
            ],
            [
                'name' => 'Maize',
                'description' => 'Cereal crop also grown for animal feed.',
                'optimal_conditions' => [
                    'temperature_min' => 10,
                    'temperature_max' => 35,
                    'humidity_min' => 50,
                    'humidity_max' => 80,
                    'ph_min' => 5.8,
                    'ph_max' => 7.0,
                ],
            ],
            [
                'name' => 'Rapeseed',
                'description' => 'Oilseed crop.',
                'optimal_conditions' => [
                    'temperature_min' => 5,
                    'temperature_max' => 27,
                    'humidity_min' => 45,
                    'humidity_max' => 70,
                    'ph_min' => 6.0,
                    'ph_max' => 7.5,
                ],
            ],
            [
                'name' => 'Potatoes',
                'description' => 'Common root crop.',
                'optimal_conditions' => [
                    'temperature_min' => 8,
                    'temperature_max' => 25,
                    'humidity_min' => 60,
                    'humidity_max' => 80,
                    'ph_min' => 5.0,
                    'ph_max' => 6.5,
                ],
            ],
            [
                'name' => 'Sugar beet',
                'description' => 'Root crop grown for sugar production.',
                'optimal_conditions' => [
                    'temperature_min' => 7,
                    'temperature_max' => 25,
                    'humidity_min' => 60,
                    'humidity_max' => 80,
                    'ph_min' => 6.0,
                    'ph_max' => 7.5,
                ],
            ],
        ];

        foreach ($crops as $crop) {
            Crop::query()->firstOrCreate(attributes: ['name' => $crop['name']], values: $crop);
        }
    }
}
