<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\Crop;
use App\Models\Field;
use App\Models\FieldData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FieldControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_includes_field_data_summary(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        FieldData::factory()->create(['field_id' => $field->id, 'data' => ['avg_ndvi' => 0.72]]);

        $this->actingAs($user)->get(route('fields.show', $field))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Field/Show')
                ->has('latestReadings', 1)
                ->where('latestReadings.0.display', '0.720')
                ->has('trendSeries.ndvi')
                ->has('recentReadings', 1)
            );
    }

    public function test_show_returns_empty_summary_for_field_without_data(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();

        $this->actingAs($user)->get(route('fields.show', $field))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('latestReadings', 0)
                ->has('recentReadings', 0)
                ->where('trendSeries', [])
            );
    }

    public function test_show_forbids_other_users_field(): void
    {
        $field = Field::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('fields.show', $field))
            ->assertForbidden();
    }

    public function test_index_filters_by_name_status_and_size(): void
    {
        $user = User::factory()->create();
        Field::factory()->for($user)->create(['name' => 'North wheat', 'status' => 'active', 'size' => 10]);
        Field::factory()->for($user)->create(['name' => 'North barley', 'status' => 'inactive', 'size' => 10]);
        Field::factory()->for($user)->create(['name' => 'North big', 'status' => 'active', 'size' => 50]);
        Field::factory()->for($user)->create(['name' => 'South', 'status' => 'active', 'size' => 10]);

        $this->actingAs($user)
            ->get(route('fields.index', ['filter' => ['name' => 'North', 'status' => 'active', 'size_min' => 5, 'size_max' => 20]]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('fields.data', 1)
                ->where('fields.data.0.name', 'North wheat')
            );
    }

    public function test_index_filters_by_crop_and_lists_crops(): void
    {
        $user = User::factory()->create();
        $wheat = Crop::create(['name' => 'Wheat']);
        $corn = Crop::create(['name' => 'Corn']);
        $wheatField = Field::factory()->for($user)->create();
        $wheatField->crops()->attach($wheat, ['planting_date' => '2026-03-01']);
        Field::factory()->for($user)->create()->crops()->attach($corn, ['planting_date' => '2026-03-01']);

        $this->actingAs($user)
            ->get(route('fields.index', ['filter' => ['crop_id' => $wheat->id]]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('fields.data', 1)
                ->where('fields.data.0.id', $wheatField->id)
                ->where('fields.data.0.crops.0.name', 'Wheat')
                ->has('crops', 2)
            );
    }

    public function test_data_redirect_filters_field_data_by_field(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('fields.data', $field))
            ->assertRedirect(route('field-data.index', ['filter[field_id]' => $field->id]));
    }

    public function test_show_includes_analytics_summary(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        Analytics::factory()->create(['field_id' => $field->id]);

        $this->actingAs($user)->get(route('fields.show', $field))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('analysisHistory', 1)
                ->has('latestAnalysis.recommendations')
                ->where('sensitivities', ['low', 'medium', 'high'])
            );
    }

    public function test_show_returns_empty_analytics_for_field_without_analyses(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();

        $this->actingAs($user)->get(route('fields.show', $field))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('latestAnalysis', null)
                ->has('analysisHistory', 0)
                ->has('topRecommendations', 0)
            );
    }

    public function test_analytics_route_redirects_to_analytics_tab(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('fields.analytics', $field))
            ->assertRedirect(route('fields.show', ['field' => $field, 'tab' => 'analytics']));
    }

    public function test_store_creates_field_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('fields.store'), [
                'name' => 'South meadow',
                'size' => 4.25,
                'status' => 'active',
                'boundaries' => [[19.0, 52.0], [19.01, 52.0], [19.01, 52.01], [19.0, 52.0]],
            ])
            ->assertRedirect(route('fields.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('fields', ['name' => 'South meadow', 'user_id' => $user->id]);
    }

    public function test_store_rejects_unknown_status_and_foreign_user_id(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)
            ->post(route('fields.store'), [
                'name' => 'Bad status',
                'size' => 1,
                'status' => 'flooded',
                'boundaries' => [[19.0, 52.0], [19.01, 52.0], [19.01, 52.01], [19.0, 52.0]],
                'user_id' => $other->id,
            ])
            ->assertSessionHasErrors('status');

        $this->actingAs($user)
            ->post(route('fields.store'), [
                'name' => 'Owned',
                'size' => 1,
                'status' => 'active',
                'boundaries' => [[19.0, 52.0], [19.01, 52.0], [19.01, 52.01], [19.0, 52.0]],
                'user_id' => $other->id,
            ]);

        $this->assertDatabaseHas('fields', ['name' => 'Owned', 'user_id' => $user->id]);
    }

    public function test_update_and_destroy_forbid_other_users_field(): void
    {
        $field = Field::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->put(route('fields.update', $field), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('fields.destroy', $field))
            ->assertForbidden();

        $this->assertDatabaseHas('fields', ['id' => $field->id]);
    }

    public function test_owner_can_update_and_destroy_field(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();

        $this->actingAs($user)
            ->put(route('fields.update', $field), ['name' => 'Renamed'])
            ->assertRedirect(route('fields.show', $field));
        $this->assertDatabaseHas('fields', ['id' => $field->id, 'name' => 'Renamed']);

        $this->actingAs($user)
            ->delete(route('fields.destroy', $field))
            ->assertRedirect(route('fields.index'));
        $this->assertDatabaseMissing('fields', ['id' => $field->id]);
    }
}
