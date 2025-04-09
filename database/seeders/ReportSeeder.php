<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reportTypes = [
            'field_summary',
            'crop_health',
            'yield_forecast',
            'soil_analysis',
            'seasonal_performance',
        ];
        $statuses = ['draft', 'generated', 'archived'];

        foreach ($users as $user) {
            $reportCount = rand(2, 4);

            for ($i = 0; $i < $reportCount; $i++) {
                $userFields = Field::where('user_id', $user->id)
                    ->pluck('id')
                    ->toArray();
                $fieldsIncluded = !empty($userFields)
                    ? $userFields
                    : [Field::first()->id];

                $reportType = $reportTypes[array_rand($reportTypes)];
                $status = $statuses[array_rand($statuses)];

                // Tytuł raportu
                $title = ucfirst($reportType) . ' - ' . now()->format('Y-m-d');

                $content = [
                    'summary' =>
                        'To jest przykładowe podsumowanie raportu typu ' .
                        $reportType .
                        '.',
                    'sections' => [
                        [
                            'title' => 'Wprowadzenie',
                            'content' =>
                                'Raport zawiera analizę danych zebranych w okresie od X do Y.',
                        ],
                        [
                            'title' => 'Metodologia',
                            'content' =>
                                'Analiza została przeprowadzona przy użyciu algorytmów uczenia maszynowego.',
                        ],
                        [
                            'title' => 'Wyniki',
                            'content' => 'Wyniki wskazują na...',
                            'data' => [
                                'value1' => rand(10, 100),
                                'value2' => rand(20, 200),
                                'growth' => rand(-10, 30),
                            ],
                        ],
                        [
                            'title' => 'Rekomendacje',
                            'content' => 'Na podstawie analizy zalecamy...',
                        ],
                    ],
                    'generated_at' => now()->toDateTimeString(),
                ];

                DB::table('reports')->insert([
                    'user_id' => $user->id,
                    'title' => $title,
                    'type' => $reportType,
                    'description' =>
                        'Przykładowy raport typu ' .
                        $reportType .
                        ' dla użytkownika ' .
                        $user->name,
                    'fields_included' => json_encode($fieldsIncluded),
                    'parameters' => json_encode([
                        'date_range' => [
                            'start' => now()
                                ->subDays(30)
                                ->format('Y-m-d'),
                            'end' => now()->format('Y-m-d'),
                        ],
                        'metrics' => ['yield', 'soil_health', 'crop_growth'],
                        'comparison' => 'year_over_year',
                    ]),
                    'content' => json_encode($content),
                    'status' => $status,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
