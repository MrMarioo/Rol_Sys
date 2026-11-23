<?php

namespace Tests\Feature;

use App\Models\Field;
use App\Models\FieldData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FieldDataControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_by_field_type_and_dates(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        $other = Field::factory()->for($user)->create();
        $match = FieldData::factory()->create(['field_id' => $field->id, 'data_type' => 'ndvi', 'collection_date' => '2026-01-15']);
        FieldData::factory()->create(['field_id' => $field->id, 'data_type' => 'weather', 'collection_date' => '2026-01-15']);
        FieldData::factory()->create(['field_id' => $field->id, 'data_type' => 'ndvi', 'collection_date' => '2026-02-15']);
        FieldData::factory()->create(['field_id' => $other->id, 'data_type' => 'ndvi', 'collection_date' => '2026-01-15']);

        $this->actingAs($user)
            ->get(route('field-data.index', ['filter' => [
                'field_id' => $field->id,
                'data_type' => 'ndvi',
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
            ]]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('fieldData.data', 1)
                ->where('fieldData.data.0.id', $match->id)
                ->where('filters.field_id', (string) $field->id)
            );
    }

    public function test_index_and_show_expose_field_name_directly(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create(['name' => 'North']);
        $record = FieldData::factory()->create(['field_id' => $field->id]);

        $this->actingAs($user)
            ->get(route('field-data.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fieldData.data.0.field.name', 'North'));

        $this->actingAs($user)
            ->get(route('field-data.show', $record))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fieldData.field.name', 'North'));
    }

    public function test_index_search_matches_type_or_field_name_and_accepts_commas(): void
    {
        $user = User::factory()->create();
        $north = Field::factory()->for($user)->create(['name' => 'North']);
        $south = Field::factory()->for($user)->create(['name' => 'South']);
        FieldData::factory()->create(['field_id' => $north->id, 'data_type' => 'weather']);
        FieldData::factory()->create(['field_id' => $south->id, 'data_type' => 'soil_moisture']);

        $this->actingAs($user)
            ->get(route('field-data.index', ['filter' => ['search' => 'North']]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('fieldData.data', 1));

        $this->actingAs($user)
            ->get(route('field-data.index', ['filter' => ['search' => 'soil_moist']]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('fieldData.data', 1));

        $this->actingAs($user)
            ->get(route('field-data.index', ['filter' => ['search' => 'a,b']]))
            ->assertOk();
    }
}
