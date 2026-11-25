<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\Field;
use App\Models\FieldData;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FieldDataSeeder extends Seeder
{
    private const DAYS = 90;

    public function run(): void
    {
        $this->ensureFieldExists();

        $fields = Field::query()->orderBy('id')->get();

        foreach ($fields as $field) {
            $this->seedField(field: $field);
        }

        mt_srand();

        $this->command?->info(sprintf('Created field data for %d fields (%d days)', $fields->count(), self::DAYS));
    }

    private function seedField(Field $field): void
    {
        mt_srand($field->id);

        $profile = match ($field->id % 3) {
            1 => ['ndvi_offset' => mt_rand(-10, 0) / 100, 'moisture_base' => mt_rand(6, 12) / 100],
            2 => ['ndvi_offset' => mt_rand(-45, -35) / 100, 'moisture_base' => mt_rand(30, 45) / 100],
            default => ['ndvi_offset' => mt_rand(-10, 5) / 100, 'moisture_base' => mt_rand(35, 50) / 100],
        };

        FieldData::query()->where('field_id', $field->id)->delete();

        $this->seedNdviData(field: $field, profile: $profile);
        $this->seedSoilMoistureData(field: $field, profile: $profile);
        $this->seedWeatherData(field: $field, profile: $profile);
        $this->seedTemperatureData(field: $field, profile: $profile);
        $this->seedFertilizerData(field: $field, profile: $profile);
        $this->seedBiomassData(field: $field, profile: $profile);
        $this->seedGrowthStageData(field: $field, profile: $profile);
        $this->seedSunlightData(field: $field, profile: $profile);
    }

    private function ensureFieldExists(): void
    {
        $field = Field::find(1);

        if (! $field) {
            if (! User::query()->whereKey(1)->exists()) {
                return;
            }

            $field = Field::query()->forceCreate([
                'id' => 1,
                'user_id' => 1,
                'name' => 'Test Field Alpha',
                'location' => 'Kraków, Poland',
                'size' => 5.5,
                'description' => 'Main test field for growth prediction',
                'boundaries' => [
                    [19.9450, 50.0647],
                    [19.9460, 50.0650],
                    [19.9465, 50.0645],
                    [19.9455, 50.0642],
                ],
                'status' => 'active',
            ]);
        }

        $crop = Crop::firstOrCreate(['name' => 'Wheat'], [
            'description' => 'Winter wheat variety',
            'optimal_conditions' => [
                'temperature_min' => 15,
                'temperature_max' => 25,
                'moisture_min' => 0.3,
                'moisture_max' => 0.7,
            ],
        ]);

        $field->crops()->syncWithoutDetaching([
            $crop->id => [
                'planting_date' => Carbon::now()->subDays(90)->format('Y-m-d'),
                'expected_harvest_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'status' => 'active',
            ],
        ]);
    }

    private function seedNdviData(Field $field, array $profile): void
    {
        $rows = [];

        for ($i = 0; $i < self::DAYS; $i++) {
            $date = Carbon::now()->subDays($i);
            $daysFromPlanting = 90 - $i;

            $ndviValues = [];
            for ($j = 0; $j < 100; $j++) {
                $baseValue = $this->calculateNdviByGrowthStage($daysFromPlanting) + $profile['ndvi_offset'];
                $seasonalTrend = sin(($daysFromPlanting / 90) * M_PI) * 0.1;
                $randomFactor = mt_rand(-10, 10) / 100;
                $anomaly = ($j % 40 === 0) ? (mt_rand(-25, -15) / 100) : 0;

                $value = max(0.1, min(0.95, $baseValue + $seasonalTrend + $randomFactor + $anomaly));
                $ndviValues[] = round($value, 3);
            }

            $rows[] = [
                'collection_date' => $date->format('Y-m-d'),
                'data' => [
                    'ndvi_values' => $ndviValues,
                    'avg_ndvi' => round(array_sum($ndviValues) / count($ndviValues), 3),
                    'coverage_percent' => mt_rand(85, 98),
                ],
                'latitude' => 50.0645 + (mt_rand(-50, 50) / 100000),
                'longitude' => 19.9458 + (mt_rand(-50, 50) / 100000),
                'metadata' => [
                    'device' => 'drone_rpi_001',
                    'camera' => 'multispectral_v2',
                    'height' => mt_rand(30, 50),
                    'weather_conditions' => $this->getWeatherCondition($date),
                    'flight_duration' => mt_rand(15, 25),
                ],
            ];
        }

        $this->insertRows(field: $field, dataType: 'ndvi', rows: $rows);
    }

    private function seedSoilMoistureData(Field $field, array $profile): void
    {
        $rows = [];

        for ($i = 0; $i < self::DAYS; $i++) {
            $date = Carbon::now()->subDays($i);

            $moistureValues = [];
            for ($j = 0; $j < 100; $j++) {
                $baseValue = $profile['moisture_base'];
                $weatherCycle = sin(($i / 7) * M_PI) * 0.15;
                $seasonalTrend = cos(($i / 30) * M_PI) * 0.1;
                $randomFactor = mt_rand(-8, 8) / 100;
                $rainfall = ($i % mt_rand(3, 7) === 0) ? mt_rand(10, 30) / 100 : 0;
                $dryPatch = ($j % 30 === 0) ? -0.2 : 0;

                $value = max(0.05, min(0.95, $baseValue + $weatherCycle + $seasonalTrend + $randomFactor + $rainfall + $dryPatch));
                $moistureValues[] = round($value, 3);
            }

            $rows[] = [
                'collection_date' => $date->format('Y-m-d'),
                'data' => [
                    'moisture_values' => $moistureValues,
                    'avg_moisture' => round(array_sum($moistureValues) / count($moistureValues), 3),
                    'dry_spots_count' => count(array_filter($moistureValues, fn ($v) => $v < 0.3)),
                ],
                'latitude' => 50.0645 + (mt_rand(-50, 50) / 100000),
                'longitude' => 19.9458 + (mt_rand(-50, 50) / 100000),
                'metadata' => [
                    'device' => 'drone_rpi_001',
                    'sensor' => 'soil_moisture_v1',
                    'depth' => '0-10cm',
                    'soil_type' => 'loamy',
                ],
            ];
        }

        $this->insertRows(field: $field, dataType: 'soil_moisture', rows: $rows);
    }

    private function seedWeatherData(Field $field, array $profile): void
    {
        $rows = [];

        for ($i = 0; $i < self::DAYS; $i++) {
            $date = Carbon::now()->subDays($i);
            $baseTemp = 18 + sin(($i / 365) * 2 * M_PI) * 8;
            $dailyVariation = mt_rand(-5, 5);

            $rows[] = [
                'collection_date' => $date->format('Y-m-d'),
                'data' => [
                    'temperature_avg' => round($baseTemp + $dailyVariation, 1),
                    'temperature_min' => round($baseTemp + $dailyVariation - mt_rand(2, 8), 1),
                    'temperature_max' => round($baseTemp + $dailyVariation + mt_rand(3, 10), 1),
                    'humidity' => mt_rand(45, 85),
                    'wind_speed' => mt_rand(2, 20),
                    'wind_direction' => mt_rand(0, 360),
                    'pressure' => mt_rand(995, 1025),
                    'rainfall' => ($i % mt_rand(3, 8) === 0) ? mt_rand(0, 25) : 0,
                ],
                'metadata' => [
                    'source' => 'local_weather_station',
                    'station_distance' => '2.5km',
                ],
            ];
        }

        $this->insertRows(field: $field, dataType: 'weather', rows: $rows);
    }

    private function seedTemperatureData(Field $field, array $profile): void
    {
        $rows = [];

        for ($i = 0; $i < self::DAYS; $i++) {
            $date = Carbon::now()->subDays($i);

            $soilTempValues = [];
            for ($j = 0; $j < 20; $j++) {
                $baseTemp = 16 + sin(($i / 365) * 2 * M_PI) * 6;
                $depthFactor = -0.5;
                $randomFactor = mt_rand(-2, 2);

                $soilTempValues[] = round($baseTemp + $depthFactor + $randomFactor, 1);
            }

            $rows[] = [
                'collection_date' => $date->format('Y-m-d'),
                'data' => [
                    'temperature_values' => $soilTempValues,
                    'avg_temperature' => round(array_sum($soilTempValues) / count($soilTempValues), 1),
                    'depth' => '5cm',
                ],
                'metadata' => [
                    'device' => 'soil_temp_sensor_array',
                    'calibration_date' => $date->copy()->subDays(30)->format('Y-m-d'),
                ],
            ];
        }

        $this->insertRows(field: $field, dataType: 'soil_temperature', rows: $rows);
    }

    private function seedFertilizerData(Field $field, array $profile): void
    {
        $fertilizerDates = [
            85 => ['type' => 'nitrogen', 'amount' => 120],
            60 => ['type' => 'phosphorus', 'amount' => 60],
            30 => ['type' => 'potassium', 'amount' => 80],
        ];

        $rows = [];

        foreach ($fertilizerDates as $daysAgo => $fertilizer) {
            $rows[] = [
                'collection_date' => Carbon::now()->subDays($daysAgo)->format('Y-m-d'),
                'data' => [
                    'fertilizer_type' => $fertilizer['type'],
                    'amount_per_hectare' => $fertilizer['amount'],
                    'total_amount' => $fertilizer['amount'] * (float) $field->size,
                    'application_method' => 'broadcast',
                    'weather_conditions' => 'optimal',
                ],
                'metadata' => [
                    'operator' => 'field_worker_001',
                    'equipment' => 'spreader_v2',
                    'application_time' => '2 hours',
                ],
            ];
        }

        $this->insertRows(field: $field, dataType: 'fertilizer_application', rows: $rows);
    }

    private function seedBiomassData(Field $field, array $profile): void
    {
        $rows = [];

        for ($i = 0; $i < self::DAYS; $i += 5) {
            $daysFromPlanting = 90 - $i;
            $biomass = $this->calculateBiomassByGrowthStage($daysFromPlanting);

            $rows[] = [
                'collection_date' => Carbon::now()->subDays($i)->format('Y-m-d'),
                'data' => [
                    'dry_biomass_kg_per_ha' => round($biomass, 2),
                    'wet_biomass_kg_per_ha' => round($biomass * 1.3, 2),
                    'plant_height_cm' => round($this->calculateHeightByGrowthStage($daysFromPlanting), 1),
                    'plant_density_per_m2' => mt_rand(200, 250),
                    'measurement_method' => 'destructive_sampling',
                ],
                'metadata' => [
                    'sample_size' => '1m2',
                    'samples_count' => 5,
                    'measurement_accuracy' => '±5%',
                ],
            ];
        }

        $this->insertRows(field: $field, dataType: 'biomass_measurement', rows: $rows);
    }

    private function seedGrowthStageData(Field $field, array $profile): void
    {
        $rows = [];

        for ($i = 0; $i < self::DAYS; $i += 7) {
            $daysFromPlanting = 90 - $i;

            $rows[] = [
                'collection_date' => Carbon::now()->subDays($i)->format('Y-m-d'),
                'data' => [
                    'growth_stage' => $this->getGrowthStage($daysFromPlanting),
                    'bbch_code' => $this->getBbchCode($daysFromPlanting),
                    'days_since_planting' => $daysFromPlanting,
                    'estimated_harvest_days' => max(0, 120 - $daysFromPlanting),
                ],
                'metadata' => [
                    'assessment_method' => 'visual_inspection',
                    'assessor' => 'agronomist_001',
                ],
            ];
        }

        $this->insertRows(field: $field, dataType: 'growth_stage', rows: $rows);
    }

    private function seedSunlightData(Field $field, array $profile): void
    {
        $rows = [];

        for ($i = 0; $i < self::DAYS; $i++) {
            $baseHours = 8 + sin(($i / 365) * 2 * M_PI) * 4;
            $cloudFactor = mt_rand(70, 100) / 100;

            $rows[] = [
                'collection_date' => Carbon::now()->subDays($i)->format('Y-m-d'),
                'data' => [
                    'sunshine_hours' => round($baseHours * $cloudFactor, 1),
                    'solar_radiation_mj_m2' => round(($baseHours * $cloudFactor) * 2.5, 1),
                    'uv_index' => mt_rand(3, 8),
                    'cloud_cover_percent' => round((1 - $cloudFactor) * 100),
                ],
                'metadata' => [
                    'measurement_device' => 'pyranometer',
                    'calibration_status' => 'active',
                ],
            ];
        }

        $this->insertRows(field: $field, dataType: 'sunlight', rows: $rows);
    }

    private function insertRows(Field $field, string $dataType, array $rows): void
    {
        $now = now();

        FieldData::query()->insert(array_map(
            fn (array $row): array => [
                'field_id' => $field->id,
                'collection_date' => $row['collection_date'],
                'data_type' => $dataType,
                'data' => json_encode($row['data']),
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
                'metadata' => json_encode($row['metadata']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $rows,
        ));
    }

    private function calculateNdviByGrowthStage($daysFromPlanting): float
    {
        if ($daysFromPlanting < 14) return 0.25;
        if ($daysFromPlanting < 30) return 0.45;
        if ($daysFromPlanting < 60) return 0.75;
        if ($daysFromPlanting < 90) return 0.85;
        if ($daysFromPlanting < 110) return 0.65;
        return 0.45;
    }

    private function calculateBiomassByGrowthStage($daysFromPlanting): float
    {
        $maxBiomass = 8000;
        $growthRate = 0.08;
        $inflectionPoint = 70;

        return $maxBiomass / (1 + exp(-$growthRate * ($daysFromPlanting - $inflectionPoint)));
    }

    private function calculateHeightByGrowthStage($daysFromPlanting): float
    {
        if ($daysFromPlanting < 14) return mt_rand(2, 5);
        if ($daysFromPlanting < 30) return mt_rand(10, 20);
        if ($daysFromPlanting < 60) return mt_rand(30, 60);
        if ($daysFromPlanting < 90) return mt_rand(70, 90);
        return mt_rand(85, 95);
    }

    private function getGrowthStage($daysFromPlanting): string
    {
        if ($daysFromPlanting < 14) return 'germination';
        if ($daysFromPlanting < 30) return 'early_vegetative';
        if ($daysFromPlanting < 60) return 'vegetative';
        if ($daysFromPlanting < 90) return 'flowering';
        if ($daysFromPlanting < 110) return 'grain_filling';
        return 'maturity';
    }

    private function getBbchCode($daysFromPlanting): int
    {
        if ($daysFromPlanting < 14) return mt_rand(0, 9);
        if ($daysFromPlanting < 30) return mt_rand(10, 19);
        if ($daysFromPlanting < 60) return mt_rand(20, 39);
        if ($daysFromPlanting < 90) return mt_rand(51, 69);
        if ($daysFromPlanting < 110) return mt_rand(71, 89);
        return mt_rand(90, 99);
    }

    private function getWeatherCondition($date): string
    {
        $conditions = ['sunny', 'partly_cloudy', 'cloudy', 'rainy'];
        return $conditions[array_rand($conditions)];
    }
}
