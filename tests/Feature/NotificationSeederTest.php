<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\Field;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AnalysisCompleted;
use App\Notifications\AnalysisFailed;
use App\Notifications\ReportGenerated;
use Database\Seeders\NotificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_notifications_have_uniform_shape(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        Analytics::factory()->for($field)->create();
        Report::factory()->for($user)->create();
        User::factory()->create();

        $this->seed(NotificationSeeder::class);

        $rows = DB::table('notifications')->get();
        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $this->assertContains($row->type, [
                AnalysisCompleted::class,
                AnalysisFailed::class,
                ReportGenerated::class,
            ]);
            $this->assertSame(['title', 'message', 'level', 'url'], array_keys(json_decode($row->data, true)));
        }
    }
}
