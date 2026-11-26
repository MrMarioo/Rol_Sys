<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Field;
use App\Models\FieldData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_only_the_users_own_data(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        $crop = Crop::query()->create(['name' => 'Wheat']);
        $field->crops()->attach($crop->id, [
            'planting_date' => now()->subMonth(),
            'expected_harvest_date' => now()->addMonths(2),
            'status' => 'active',
        ]);
        FieldData::factory()->create(['field_id' => $field->id, 'collection_date' => now()->subDay()]);
        FieldData::factory()->create(['field_id' => Field::factory()->create()->id, 'collection_date' => now()->subDay()]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard/Index')
                ->where('stats.totalFields', 1)
                ->where('stats.activeCrops', 1)
                ->where('stats.recentDataCollections', 1)
                ->where('fields.0.current_crop.name', 'Wheat')
                ->has('fieldData', 1)
                ->missing('user')
            );
    }
}
