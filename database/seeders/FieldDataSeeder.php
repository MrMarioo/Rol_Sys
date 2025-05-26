<?php

namespace Database\Seeders;

use App\Models\FieldData;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FieldDataSeeder extends Seeder
{

    public function run(): void
    {
        FieldData::where('field_id', 1)->delete();

        $this->seedNdviData();

        $this->seedSoilMoistureData();

        $this->command->info('Created fieldData for field ID=1');
    }

    /**
     * Generuj dane NDVI
     */
    private function seedNdviData(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i);

            $ndviValues = [];
            for ($j = 0; $j < 100; $j++) {
                $baseValue = 0.65;

                $trend = $i > 20 ? -0.005 * $i : 0.002 * $i;

                $randomFactor = mt_rand(-15, 15) / 100;

                $anomaly = ($j % 33 === 0) ? (mt_rand(-40, -20) / 100) : 0;

                $value = max(0, min(1, $baseValue + $trend + $randomFactor + $anomaly));
                $ndviValues[] = round($value, 2);
            }

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'ndvi',
                'data' => ['ndvi_values' => $ndviValues],
                'latitude' => 50.4 + (mt_rand(-100, 100) / 10000),
                'longitude' => 20.3 + (mt_rand(-100, 100) / 10000),
                'metadata' => [
                    'device' => 'drone_rpi_001',
                    'camera' => 'multispectral_v2',
                    'height' => mt_rand(30, 50),
                    'weather' => ['temp' => mt_rand(15, 30), 'wind' => mt_rand(0, 15)]
                ]
            ]);
        }
    }


    private function seedSoilMoistureData(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i);

            $moistureValues = [];
            for ($j = 0; $j < 100; $j++) {
                $baseValue = 0.45;

                $cycle = $i < 15 ? 0.2 : -0.1;

                $randomFactor = mt_rand(-10, 10) / 100;

                $rainfall = ($i % 5 === 0) ? 0.2 : 0;

                $dryPatch = ($j % 25 === 0) ? -0.3 : 0;

                $value = max(0, min(1, $baseValue + $cycle + $randomFactor + $rainfall + $dryPatch));
                $moistureValues[] = round($value, 2);
            }

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'soil_moisture',
                'data' => ['moisture_values' => $moistureValues],
                'latitude' => 50.4 + (mt_rand(-100, 100) / 10000),
                'longitude' => 20.3 + (mt_rand(-100, 100) / 10000),
                'metadata' => [
                    'device' => 'drone_rpi_001',
                    'sensor' => 'soil_moisture_sensor_v1',
                    'height' => mt_rand(10, 20),
                    'weather' => ['temp' => mt_rand(15, 30), 'humidity' => mt_rand(30, 90)]
                ]
            ]);
        }
    }
}
