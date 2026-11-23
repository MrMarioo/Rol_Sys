<?php

namespace Tests\Feature;

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
}
