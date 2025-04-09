<?php

namespace Database\Seeders;

use App\Models\Field;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = Field::all();
        $analysisTypes = [
            'yield_prediction',
            'soil_health',
            'irrigation_needs',
            'pest_risk',
            'nutrient_recommendation',
        ];

        foreach ($fields as $field) {
            $analysisCount = rand(1, 3);

            for ($i = 0; $i < $analysisCount; $i++) {
                $analysisType = $analysisTypes[array_rand($analysisTypes)];
                $date = now()->subDays(rand(1, 30));

                $results = [];
                $recommendations = '';

                switch ($analysisType) {
                    case 'yield_prediction':
                        $results = [
                            'predicted_yield' => rand(300, 800) / 10, // 30.0 - 80.0 dt/ha
                            'confidence' => rand(70, 95),
                            'factors' => [
                                'weather' => rand(1, 5),
                                'soil' => rand(1, 5),
                                'pests' => rand(1, 5),
                            ],
                        ];
                        $recommendations =
                            'Zalecamy optymalizację nawadniania w okresie wzrostu. Monitoruj poziom szkodników.';
                        break;
                    case 'soil_health':
                        $results = [
                            'overall_health' => rand(1, 5),
                            'components' => [
                                'organic_matter' => rand(1, 5),
                                'microbial_activity' => rand(1, 5),
                                'structure' => rand(1, 5),
                                'compaction' => rand(1, 5),
                            ],
                        ];
                        $recommendations =
                            'Zalecamy zastosowanie nawozów organicznych, aby poprawić strukturę gleby.';
                        break;
                    case 'irrigation_needs':
                        $results = [
                            'water_deficit' => rand(0, 50), // mm
                            'recommended_irrigation' => rand(0, 40), // mm
                            'next_rainfall_forecast' => [
                                'expected' => rand(0, 1) === 1,
                                'amount' => rand(0, 30), // mm
                            ],
                        ];
                        $recommendations =
                            'Zalecamy nawadnianie w ilości 25mm w ciągu najbliższych 3 dni.';
                        break;
                    case 'pest_risk':
                        $results = [
                            'overall_risk' => rand(1, 5),
                            'specific_risks' => [
                                'aphids' => rand(1, 5),
                                'fungal_diseases' => rand(1, 5),
                                'weeds' => rand(1, 5),
                            ],
                        ];
                        $recommendations =
                            'Wykryto zwiększone ryzyko chorób grzybowych. Zalecamy monitoring i profilaktyczne opryski.';
                        break;
                    case 'nutrient_recommendation':
                        $results = [
                            'nitrogen' => [
                                'current' => rand(10, 50),
                                'optimal' => rand(40, 80),
                                'deficiency' => true,
                            ],
                            'phosphorus' => [
                                'current' => rand(20, 60),
                                'optimal' => rand(40, 70),
                                'deficiency' => rand(0, 1) === 1,
                            ],
                            'potassium' => [
                                'current' => rand(30, 70),
                                'optimal' => rand(50, 90),
                                'deficiency' => rand(0, 1) === 1,
                            ],
                        ];
                        $recommendations =
                            'Zalecamy zastosowanie nawozu azotowego w dawce 80 kg/ha.';
                        break;
                }

                DB::table('analytics')->insert([
                    'field_id' => $field->id,
                    'analysis_type' => $analysisType,
                    'analysis_date' => $date->format('Y-m-d'),
                    'results' => json_encode($results),
                    'recommendations' => $recommendations,
                    'parameters' => json_encode([
                        'model_version' => '1.0.3',
                        'data_sources' => [
                            'field_data',
                            'weather_api',
                            'soil_sensors',
                        ],
                        'confidence_level' => rand(70, 95),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
