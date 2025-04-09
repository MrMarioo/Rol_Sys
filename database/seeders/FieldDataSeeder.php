<?php

namespace Database\Seeders;

use App\Models\Field;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = Field::all();
        $dataTypes = [
            'temperature',
            'humidity',
            'soil_moisture',
            'ph',
            'nitrogen',
            'phosphorus',
            'potassium',
            'rainfall',
        ];

        foreach ($fields as $field) {
            for ($day = 0; $day < 30; $day++) {
                $date = now()->subDays($day);
                foreach ($dataTypes as $dataType) {
                    $data = [];

                    switch ($dataType) {
                        case 'temperature':
                            $data = [
                                'avg' => rand(100, 300) / 10, // 10.0 - 30.0 °C
                                'min' => rand(50, 150) / 10, // 5.0 - 15.0 °C
                                'max' => rand(250, 350) / 10, // 25.0 - 35.0 °C
                            ];
                            break;
                        case 'humidity':
                            $data = [
                                'avg' => rand(400, 800) / 10, // 40.0 - 80.0 %
                            ];
                            break;
                        case 'soil_moisture':
                            $data = [
                                'avg' => rand(300, 600) / 10, // 30.0 - 60.0 %
                            ];
                            break;
                        case 'ph':
                            $data = [
                                'avg' => rand(50, 80) / 10, // 5.0 - 8.0 pH
                            ];
                            break;
                        case 'nitrogen':
                        case 'phosphorus':
                        case 'potassium':
                            $data = [
                                'level' => rand(1, 5), // 1-5 (niski/średni/wysoki)
                                'value' => rand(10, 100), // ppm
                            ];
                            break;
                        case 'rainfall':
                            $data = [
                                'total' => rand(0, 500) / 10,
                            ];
                            break;
                    }

                    DB::table('field_data')->insert([
                        'field_id' => $field->id,
                        'collection_date' => $date->format('Y-m-d'),
                        'data_type' => $dataType,
                        'data' => json_encode($data),
                        'latitude' => rand(5100000, 5200000) / 100000,
                        'longitude' => rand(2000000, 2100000) / 100000,
                        'metadata' => json_encode([
                            'source' => $dataTypes[array_rand($dataTypes)],
                            'accuracy' => rand(80, 99),
                            'timestamp' => $date->format('Y-m-d H:i:s'),
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
