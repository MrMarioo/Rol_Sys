<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $notificationTypes = [
            'App\\Notifications\\FieldDataAlert',
            'App\\Notifications\\WeatherWarning',
            'App\\Notifications\\AnalysisComplete',
            'App\\Notifications\\ReportGenerated',
            'App\\Notifications\\CropWarning',
        ];

        foreach ($users as $user) {
            $notificationCount = rand(3, 8);

            for ($i = 0; $i < $notificationCount; $i++) {
                $notificationType =
                    $notificationTypes[array_rand($notificationTypes)];

                $data = [];

                switch ($notificationType) {
                    case 'App\\Notifications\\FieldDataAlert':
                        $data = [
                            'field_id' => rand(1, 10),
                            'field_name' => 'Pole ' . rand(1, 10),
                            'alert_type' => 'moisture_low',
                            'message' =>
                                'Wykryto niski poziom wilgotności gleby.',
                            'value' => rand(10, 30) . '%',
                            'threshold' => '35%',
                            'timestamp' => now()
                                ->subHours(rand(1, 48))
                                ->toDateTimeString(),
                        ];
                        break;
                    case 'App\\Notifications\\WeatherWarning':
                        $data = [
                            'warning_type' => 'heavy_rain',
                            'message' =>
                                'Prognozowane są intensywne opady deszczu.',
                            'expected_at' => now()
                                ->addHours(rand(6, 24))
                                ->toDateTimeString(),
                            'duration' => rand(1, 6) . ' godzin',
                            'severity' => 'medium',
                        ];
                        break;
                    case 'App\\Notifications\\AnalysisComplete':
                        $data = [
                            'analysis_id' => rand(1, 20),
                            'analysis_type' => 'yield_prediction',
                            'field_name' => 'Pole ' . rand(1, 10),
                            'message' => 'Analiza pola została zakończona.',
                            'result_summary' =>
                                'Przewidywany plon: ' . rand(30, 80) . ' dt/ha',
                        ];
                        break;
                    case 'App\\Notifications\\ReportGenerated':
                        $data = [
                            'report_id' => rand(1, 15),
                            'report_title' =>
                                'Raport - ' . now()->format('Y-m-d'),
                            'message' =>
                                'Twój raport został wygenerowany i jest gotowy do pobrania.',
                        ];
                        break;
                    case 'App\\Notifications\\CropWarning':
                        $data = [
                            'field_id' => rand(1, 10),
                            'field_name' => 'Pole ' . rand(1, 10),
                            'crop_name' => [
                                'Pszenica',
                                'Kukurydza',
                                'Rzepak',
                                'Ziemniaki',
                            ][rand(0, 3)],
                            'warning_type' => 'pest_detected',
                            'message' =>
                                'Wykryto potencjalne zagrożenie szkodnikami.',
                            'recommended_action' =>
                                'Zalecamy inspekcję pola i rozważenie oprysków.',
                        ];
                        break;
                }

                $readAt =
                    rand(1, 10) <= 7 ? now()->subHours(rand(1, 24)) : null;

                DB::table('notifications')->insert([
                    'id' => Str::uuid()->toString(),
                    'type' => $notificationType,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode($data),
                    'read_at' => $readAt,
                    'created_at' => now()->subDays(rand(1, 14)),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
