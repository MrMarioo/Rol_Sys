<?php

namespace Database\Seeders;

use App\Models\Analytics;
use App\Models\Field;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AnalysisCompleted;
use App\Notifications\AnalysisFailed;
use App\Notifications\ReportGenerated;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (User::all() as $user) {
            $candidates = $this->candidatesFor($user);

            if ($candidates === []) {
                continue;
            }

            for ($i = 0, $count = rand(3, 8); $i < $count; $i++) {
                $notification = $candidates[array_rand($candidates)];

                DB::table('notifications')->insert([
                    'id' => Str::uuid()->toString(),
                    'type' => $notification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode($notification->toArray($user)),
                    'read_at' => rand(1, 10) <= 7 ? now()->subHours(rand(1, 24)) : null,
                    'created_at' => now()->subDays(rand(1, 14)),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * @return array<int, Notification>
     */
    private function candidatesFor(User $user): array
    {
        $fieldIds = Field::where('user_id', $user->id)->pluck('id');

        return [
            ...Analytics::whereIn('field_id', $fieldIds)->with('field')->get()
                ->map(fn (Analytics $analytics) => new AnalysisCompleted($analytics))->all(),
            ...Field::whereIn('id', $fieldIds)->get()
                ->map(fn (Field $field) => new AnalysisFailed($field))->all(),
            ...Report::where('user_id', $user->id)->get()
                ->map(fn (Report $report) => new ReportGenerated($report))->all(),
        ];
    }
}
