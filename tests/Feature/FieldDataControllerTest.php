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

    public function test_store_saves_reading_for_owned_field(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('field-data.store'), [
                'field_id' => $field->id,
                'collection_date' => '2026-05-01',
                'data_type' => 'soil_moisture',
                'data_json' => json_encode(['moisture_values' => [0.4, 0.5]]),
            ])
            ->assertRedirect(route('field-data.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('field_data', ['field_id' => $field->id, 'data_type' => 'soil_moisture']);
    }

    public function test_store_rejects_foreign_field_and_unknown_type(): void
    {
        $user = User::factory()->create();
        $foreignField = Field::factory()->create();

        $this->actingAs($user)
            ->post(route('field-data.store'), [
                'field_id' => $foreignField->id,
                'collection_date' => '2026-05-01',
                'data_type' => 'radiation',
                'data_json' => json_encode(['value' => 1]),
            ])
            ->assertSessionHasErrors(['field_id', 'data_type']);

        $this->assertDatabaseCount('field_data', 0);
    }

    public function test_owner_can_update_and_destroy_reading(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        $reading = FieldData::factory()->create(['field_id' => $field->id]);

        $this->actingAs($user)
            ->put(route('field-data.update', $reading), [
                'field_id' => $field->id,
                'collection_date' => '2026-06-01',
                'data_type' => 'temperature',
                'data' => ['value' => 21.5],
            ])
            ->assertRedirect(route('field-data.index'));
        $this->assertSame('temperature', $reading->fresh()->data_type);

        $this->actingAs($user)
            ->delete(route('field-data.destroy', $reading))
            ->assertRedirect(route('field-data.index'));
        $this->assertDatabaseMissing('field_data', ['id' => $reading->id]);
    }

    public function test_other_user_cannot_update_or_destroy_reading(): void
    {
        $reading = FieldData::factory()->create();
        $intruder = User::factory()->create();
        $intruderField = Field::factory()->for($intruder)->create();

        $this->actingAs($intruder)
            ->put(route('field-data.update', $reading), [
                'field_id' => $intruderField->id,
                'collection_date' => '2026-06-01',
                'data_type' => 'ndvi',
                'data' => ['ndvi_values' => [0.5]],
            ])
            ->assertForbidden();

        $this->actingAs($intruder)->delete(route('field-data.destroy', $reading))->assertForbidden();
        $this->assertDatabaseHas('field_data', ['id' => $reading->id, 'field_id' => $reading->field_id]);
    }
}
