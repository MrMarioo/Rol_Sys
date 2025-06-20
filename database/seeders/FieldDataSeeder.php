<?php

namespace Database\Seeders;

use App\Models\FieldData;
use App\Models\Field;
use App\Models\Crop;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FieldDataSeeder extends Seeder
{
    public function run(): void
    {
        FieldData::where('field_id', 1)->delete();

        // Upewnij się, że pole 1 istnieje z uprawą
        $this->ensureFieldExists();

        // Generuj dane dla ostatnich 90 dni (pełny sezon)
        $this->seedNdviData();
        $this->seedSoilMoistureData();
        $this->seedWeatherData();
        $this->seedTemperatureData();
        $this->seedFertilizerData();
        $this->seedBiomassData();
        $this->seedGrowthStageData();
        $this->seedSunlightData();

        $this->command->info('Created comprehensive field data for field ID=1 (90 days)');
    }

    private function ensureFieldExists(): void
    {
        $field = Field::find(1);
        if (!$field) {
            $field = Field::create([
                'user_id' => 1,
                'name' => 'Test Field Alpha',
                'location' => 'Kraków, Poland',
                'size' => 5.5,
                'description' => 'Main test field for growth prediction',
                'boundaries' => [
                    ['lat' => 50.0647, 'lng' => 19.9450],
                    ['lat' => 50.0650, 'lng' => 19.9460],
                    ['lat' => 50.0645, 'lng' => 19.9465],
                    ['lat' => 50.0642, 'lng' => 19.9455],
                ],
                'status' => 'active'
            ]);
        }

        // Dodaj uprawę jeśli nie istnieje
        $crop = Crop::firstOrCreate(['name' => 'Wheat'], [
            'description' => 'Winter wheat variety',
            'optimal_conditions' => [
                'temperature_min' => 15,
                'temperature_max' => 25,
                'moisture_min' => 0.3,
                'moisture_max' => 0.7
            ]
        ]);

        // Przypisz uprawę do pola z datą sadzenia 90 dni temu
        $field->crops()->syncWithoutDetaching([
            $crop->id => [
                'planting_date' => Carbon::now()->subDays(90)->format('Y-m-d'),
                'expected_harvest_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'status' => 'active'
            ]
        ]);
    }

    private function seedNdviData(): void
    {
        for ($i = 0; $i < 90; $i++) {
            $date = Carbon::now()->subDays($i);
            $daysFromPlanting = 90 - $i;

            $ndviValues = [];
            for ($j = 0; $j < 100; $j++) {
                // Realistyczna krzywa wzrostu NDVI dla pszenicy
                $baseValue = $this->calculateNdviByGrowthStage($daysFromPlanting);

                $seasonalTrend = sin(($daysFromPlanting / 90) * M_PI) * 0.1;
                $randomFactor = mt_rand(-10, 10) / 100;
                $anomaly = ($j % 40 === 0) ? (mt_rand(-25, -15) / 100) : 0;

                $value = max(0.1, min(0.95, $baseValue + $seasonalTrend + $randomFactor + $anomaly));
                $ndviValues[] = round($value, 3);
            }

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'ndvi',
                'data' => [
                    'ndvi_values' => $ndviValues,
                    'avg_ndvi' => round(array_sum($ndviValues) / count($ndviValues), 3),
                    'coverage_percent' => mt_rand(85, 98)
                ],
                'latitude' => 50.0645 + (mt_rand(-50, 50) / 100000),
                'longitude' => 19.9458 + (mt_rand(-50, 50) / 100000),
                'metadata' => [
                    'device' => 'drone_rpi_001',
                    'camera' => 'multispectral_v2',
                    'height' => mt_rand(30, 50),
                    'weather_conditions' => $this->getWeatherCondition($date),
                    'flight_duration' => mt_rand(15, 25)
                ]
            ]);
        }
    }

    private function seedSoilMoistureData(): void
    {
        for ($i = 0; $i < 90; $i++) {
            $date = Carbon::now()->subDays($i);

            $moistureValues = [];
            for ($j = 0; $j < 100; $j++) {
                $baseValue = 0.45;

                // Symulacja cykli pogodowych
                $weatherCycle = sin(($i / 7) * M_PI) * 0.15; // Tygodniowy cykl
                $seasonalTrend = cos(($i / 30) * M_PI) * 0.1; // Miesięczny trend
                $randomFactor = mt_rand(-8, 8) / 100;

                // Symulacja opadów co 3-7 dni
                $rainfall = ($i % mt_rand(3, 7) === 0) ? mt_rand(10, 30) / 100 : 0;

                // Suche plamy w polu
                $dryPatch = ($j % 30 === 0) ? -0.2 : 0;

                $value = max(0.05, min(0.95, $baseValue + $weatherCycle + $seasonalTrend + $randomFactor + $rainfall + $dryPatch));
                $moistureValues[] = round($value, 3);
            }

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'soil_moisture',
                'data' => [
                    'moisture_values' => $moistureValues,
                    'avg_moisture' => round(array_sum($moistureValues) / count($moistureValues), 3),
                    'dry_spots_count' => count(array_filter($moistureValues, fn($v) => $v < 0.3))
                ],
                'latitude' => 50.0645 + (mt_rand(-50, 50) / 100000),
                'longitude' => 19.9458 + (mt_rand(-50, 50) / 100000),
                'metadata' => [
                    'device' => 'drone_rpi_001',
                    'sensor' => 'soil_moisture_v1',
                    'depth' => '0-10cm',
                    'soil_type' => 'loamy'
                ]
            ]);
        }
    }

    private function seedWeatherData(): void
    {
        for ($i = 0; $i < 90; $i++) {
            $date = Carbon::now()->subDays($i);

            // Realistyczne dane pogodowe dla Polski
            $baseTemp = 18 + sin(($i / 365) * 2 * M_PI) * 8; // Sezonowe zmiany
            $dailyVariation = mt_rand(-5, 5);

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'weather',
                'data' => [
                    'temperature_avg' => round($baseTemp + $dailyVariation, 1),
                    'temperature_min' => round($baseTemp + $dailyVariation - mt_rand(2, 8), 1),
                    'temperature_max' => round($baseTemp + $dailyVariation + mt_rand(3, 10), 1),
                    'humidity' => mt_rand(45, 85),
                    'wind_speed' => mt_rand(2, 20),
                    'wind_direction' => mt_rand(0, 360),
                    'pressure' => mt_rand(995, 1025),
                    'rainfall' => ($i % mt_rand(3, 8) === 0) ? mt_rand(0, 25) : 0
                ],
                'metadata' => [
                    'source' => 'local_weather_station',
                    'station_distance' => '2.5km'
                ]
            ]);
        }
    }

    private function seedTemperatureData(): void
    {
        for ($i = 0; $i < 90; $i++) {
            $date = Carbon::now()->subDays($i);

            $soilTempValues = [];
            for ($j = 0; $j < 20; $j++) { // Mniej punktów dla temperatury gleby
                $baseTemp = 16 + sin(($i / 365) * 2 * M_PI) * 6;
                $depthFactor = -0.5; // Temperatura gleby niższa o 0.5°C
                $randomFactor = mt_rand(-2, 2);

                $soilTempValues[] = round($baseTemp + $depthFactor + $randomFactor, 1);
            }

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'soil_temperature',
                'data' => [
                    'temperature_values' => $soilTempValues,
                    'avg_temperature' => round(array_sum($soilTempValues) / count($soilTempValues), 1),
                    'depth' => '5cm'
                ],
                'metadata' => [
                    'device' => 'soil_temp_sensor_array',
                    'calibration_date' => $date->subDays(30)->format('Y-m-d')
                ]
            ]);
        }
    }

    private function seedFertilizerData(): void
    {
        // Symulacja aplikacji nawozów w kluczowych momentach
        $fertilizerDates = [
            85 => ['type' => 'nitrogen', 'amount' => 120], // Początek sezonu
            60 => ['type' => 'phosphorus', 'amount' => 60], // Wzrost wegetatywny
            30 => ['type' => 'potassium', 'amount' => 80],  // Kwitnienie
        ];

        foreach ($fertilizerDates as $daysAgo => $fertilizer) {
            $date = Carbon::now()->subDays($daysAgo);

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'fertilizer_application',
                'data' => [
                    'fertilizer_type' => $fertilizer['type'],
                    'amount_per_hectare' => $fertilizer['amount'],
                    'total_amount' => $fertilizer['amount'] * 5.5, // rozmiar pola
                    'application_method' => 'broadcast',
                    'weather_conditions' => 'optimal'
                ],
                'metadata' => [
                    'operator' => 'field_worker_001',
                    'equipment' => 'spreader_v2',
                    'application_time' => '2 hours'
                ]
            ]);
        }
    }

    private function seedBiomassData(): void
    {
        // Dane biomasy - kluczowe dla trenowania modelu predykcji
        for ($i = 0; $i < 90; $i += 5) { // Co 5 dni
            $date = Carbon::now()->subDays($i);
            $daysFromPlanting = 90 - $i;

            // Realistyczna krzywa wzrostu biomasy
            $biomass = $this->calculateBiomassByGrowthStage($daysFromPlanting);

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'biomass_measurement',
                'data' => [
                    'dry_biomass_kg_per_ha' => round($biomass, 2),
                    'wet_biomass_kg_per_ha' => round($biomass * 1.3, 2),
                    'plant_height_cm' => round($this->calculateHeightByGrowthStage($daysFromPlanting), 1),
                    'plant_density_per_m2' => mt_rand(200, 250),
                    'measurement_method' => 'destructive_sampling'
                ],
                'metadata' => [
                    'sample_size' => '1m2',
                    'samples_count' => 5,
                    'measurement_accuracy' => '±5%'
                ]
            ]);
        }
    }

    private function seedGrowthStageData(): void
    {
        for ($i = 0; $i < 90; $i += 7) { // Co tydzień
            $date = Carbon::now()->subDays($i);
            $daysFromPlanting = 90 - $i;

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'growth_stage',
                'data' => [
                    'growth_stage' => $this->getGrowthStage($daysFromPlanting),
                    'bbch_code' => $this->getBbchCode($daysFromPlanting),
                    'days_since_planting' => $daysFromPlanting,
                    'estimated_harvest_days' => max(0, 120 - $daysFromPlanting)
                ],
                'metadata' => [
                    'assessment_method' => 'visual_inspection',
                    'assessor' => 'agronomist_001'
                ]
            ]);
        }
    }

    private function seedSunlightData(): void
    {
        for ($i = 0; $i < 90; $i++) {
            $date = Carbon::now()->subDays($i);

            // Realistyczne dane nasłonecznienia dla Polski
            $baseHours = 8 + sin(($i / 365) * 2 * M_PI) * 4; // Sezonowe zmiany
            $cloudFactor = mt_rand(70, 100) / 100; // Zachmurzenie

            FieldData::create([
                'field_id' => 1,
                'collection_date' => $date->format('Y-m-d'),
                'data_type' => 'sunlight',
                'data' => [
                    'sunshine_hours' => round($baseHours * $cloudFactor, 1),
                    'solar_radiation_mj_m2' => round(($baseHours * $cloudFactor) * 2.5, 1),
                    'uv_index' => mt_rand(3, 8),
                    'cloud_cover_percent' => round((1 - $cloudFactor) * 100)
                ],
                'metadata' => [
                    'measurement_device' => 'pyranometer',
                    'calibration_status' => 'active'
                ]
            ]);
        }
    }

    // Funkcje pomocnicze do kalkulacji realistycznych wartości

    private function calculateNdviByGrowthStage($daysFromPlanting): float
    {
        if ($daysFromPlanting < 14) return 0.25; // Kiełkowanie
        if ($daysFromPlanting < 30) return 0.45; // Wzrost wczesny
        if ($daysFromPlanting < 60) return 0.75; // Wzrost wegetatywny
        if ($daysFromPlanting < 90) return 0.85; // Kwitnienie
        if ($daysFromPlanting < 110) return 0.65; // Dojrzewanie
        return 0.45; // Dojrzałość
    }

    private function calculateBiomassByGrowthStage($daysFromPlanting): float
    {
        // Logistyczna krzywa wzrostu biomasy
        $maxBiomass = 8000; // kg/ha
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
        if ($daysFromPlanting < 14) return mt_rand(0, 9);   // Kiełkowanie
        if ($daysFromPlanting < 30) return mt_rand(10, 19); // Rozwój liści
        if ($daysFromPlanting < 60) return mt_rand(20, 39); // Krzewienie/wydłużanie
        if ($daysFromPlanting < 90) return mt_rand(51, 69); // Kwitnienie
        if ($daysFromPlanting < 110) return mt_rand(71, 89); // Dojrzewanie owoców
        return mt_rand(90, 99); // Dojrzałość
    }

    private function getWeatherCondition($date): string
    {
        $conditions = ['sunny', 'partly_cloudy', 'cloudy', 'rainy'];
        return $conditions[array_rand($conditions)];
    }
}
