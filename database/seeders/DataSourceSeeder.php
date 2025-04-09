<?php

namespace Database\Seeders;

use App\Models\DataSource;
use Illuminate\Database\Seeder;

class DataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataSources = [
            [
                'name' => 'Stacja pogodowa',
                'type' => 'sensor',
                'configuration' => json_encode([
                    'api_key' => 'abc123',
                    'update_interval' => 60,
                    'sensors' => ['temperature', 'humidity', 'rainfall'],
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'Czujniki glebowe',
                'type' => 'iot',
                'configuration' => json_encode([
                    'device_id' => 'soil001',
                    'update_interval' => 120,
                    'sensors' => ['moisture', 'ph', 'nutrients'],
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'Dane satelitarne',
                'type' => 'api',
                'configuration' => json_encode([
                    'provider' => 'satellite_service',
                    'api_key' => 'sat123',
                    'update_interval' => 1440,
                    'image_type' => 'ndvi',
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'Ręczne pomiary',
                'type' => 'manual',
                'configuration' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Dron z kamerą',
                'type' => 'iot',
                'configuration' => json_encode([
                    'device_id' => 'drone001',
                    'camera_type' => 'thermal',
                    'flight_height' => 50,
                    'resolution' => '4K',
                ]),
                'is_active' => true,
            ],
        ];

        foreach ($dataSources as $dataSource) {
            DataSource::create($dataSource);
        }
    }
}
