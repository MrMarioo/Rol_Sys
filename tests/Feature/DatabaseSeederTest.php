<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_seed_gives_demo_user_a_complete_workspace(): void
    {
        $this->seed();

        $user = User::query()->where('email', UserSeeder::DEMO_EMAIL)->firstOrFail();

        $this->assertGreaterThan(0, $user->fields()->count());
        $this->assertSame(5, $user->reports()->where('status', ReportStatus::Generated)->count());
        $this->assertSame(0, Report::query()->whereJsonLength('content', 0)->count());

        $this->post('/login', ['email' => UserSeeder::DEMO_EMAIL, 'password' => UserSeeder::DEMO_PASSWORD])
            ->assertRedirect('/');
    }
}
