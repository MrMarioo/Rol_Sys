<?php

namespace Tests\Feature\Api;

use App\Jobs\AnalyzeFieldDataJob;
use App\Models\Field;
use App\Models\FieldData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FieldDataApiTest extends TestCase
{
    use RefreshDatabase;

    private function ndviPayload(Field $field): array
    {
        return [
            'field_id' => $field->id,
            'collection_date' => '2026-05-01',
            'data_type' => 'ndvi',
            'data' => ['ndvi_values' => array_fill(0, 100, 0.6)],
            'latitude' => 52.0,
            'longitude' => 19.0,
            'metadata' => ['device' => 'drone-1'],
        ];
    }

    public function test_drone_endpoint_requires_authentication(): void
    {
        $field = Field::factory()->create();

        $this->postJson('/api/drone/field-data', $this->ndviPayload($field))->assertUnauthorized();

        $this->assertDatabaseCount('field_data', 0);
    }

    public function test_drone_endpoint_rejects_other_users_field(): void
    {
        $field = Field::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/drone/field-data', $this->ndviPayload($field))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('field_id');
    }

    public function test_drone_endpoint_stores_reading_for_owned_field(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/drone/field-data', $this->ndviPayload($field))->assertCreated();

        $this->assertDatabaseHas('field_data', ['field_id' => $field->id, 'data_type' => 'ndvi']);
    }

    public function test_drone_endpoint_rejects_unknown_data_type(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/drone/field-data', [...$this->ndviPayload($field), 'data_type' => 'anything'])
            ->assertJsonValidationErrors('data_type');
    }

    public function test_store_rejects_other_users_field(): void
    {
        Queue::fake();
        $field = Field::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/field-data', $this->ndviPayload($field))
            ->assertJsonValidationErrors('field_id');

        Queue::assertNothingPushed();
    }

    public function test_store_queues_analysis_for_owned_field(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/field-data', $this->ndviPayload($field))->assertCreated();

        Queue::assertPushed(AnalyzeFieldDataJob::class);
    }

    public function test_show_and_for_field_forbid_other_users_records(): void
    {
        $fieldData = FieldData::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/field-data/{$fieldData->id}")->assertForbidden();
        $this->getJson("/api/fields/{$fieldData->field_id}/data")->assertForbidden();
    }

    public function test_for_field_returns_owner_data_as_resource(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        FieldData::factory()->create(['field_id' => $field->id]);
        Sanctum::actingAs($user);

        $this->getJson("/api/fields/{$field->id}/data")
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('field.id', $field->id)
            ->assertJsonPath('field.name', $field->name);
    }

    public function test_api_routes_are_rate_limited(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/field-data/999')->assertHeader('X-RateLimit-Limit');
    }
}
