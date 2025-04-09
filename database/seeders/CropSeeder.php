<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;

class CropSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $crops = [
            [
                'name' => 'Pszenica',
                'description' => 'Popularna uprawa zbożowa.',
                'optimal_conditions' => json_encode([
                    'temperature_min' => 4,
                    'temperature_max' => 30,
                    'humidity_min' => 40,
                    'humidity_max' => 70,
                    'ph_min' => 6.0,
                    'ph_max' => 7.5,
                ]),
            ],
            [
                'name' => 'Kukurydza',
                'description' =>
                    'Uprawa zbożowa wykorzystywana również jako pasza.',
                'optimal_conditions' => json_encode([
                    'temperature_min' => 10,
                    'temperature_max' => 35,
                    'humidity_min' => 50,
                    'humidity_max' => 80,
                    'ph_min' => 5.8,
                    'ph_max' => 7.0,
                ]),
            ],
            [
                'name' => 'Rzepak',
                'description' => 'Uprawa oleista.',
                'optimal_conditions' => json_encode([
                    'temperature_min' => 5,
                    'temperature_max' => 27,
                    'humidity_min' => 45,
                    'humidity_max' => 70,
                    'ph_min' => 6.0,
                    'ph_max' => 7.5,
                ]),
            ],
            [
                'name' => 'Ziemniaki',
                'description' => 'Popularna uprawa okopowa.',
                'optimal_conditions' => json_encode([
                    'temperature_min' => 8,
                    'temperature_max' => 25,
                    'humidity_min' => 60,
                    'humidity_max' => 80,
                    'ph_min' => 5.0,
                    'ph_max' => 6.5,
                ]),
            ],
            [
                'name' => 'Buraki cukrowe',
                'description' =>
                    'Uprawa okopowa wykorzystywana do produkcji cukru.',
                'optimal_conditions' => json_encode([
                    'temperature_min' => 7,
                    'temperature_max' => 25,
                    'humidity_min' => 60,
                    'humidity_max' => 80,
                    'ph_min' => 6.0,
                    'ph_max' => 7.5,
                ]),
            ],
        ];

        foreach ($crops as $crop) {
            Crop::create($crop);
        }
    }
}
