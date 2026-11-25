<?php

namespace Tests\Feature;

use App\Models\Field;
use App\Models\FieldData;
use Database\Seeders\FieldDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_field_gets_ndvi_and_moisture(): void
    {
        $fields = Field::factory()->count(2)->create();

        $this->seed(FieldDataSeeder::class);

        foreach ($fields as $field) {
            $this->assertGreaterThanOrEqual(90, FieldData::where('field_id', $field->id)->where('data_type', 'ndvi')->count());
            $this->assertGreaterThanOrEqual(90, FieldData::where('field_id', $field->id)->where('data_type', 'soil_moisture')->count());
        }
    }

    public function test_reseeding_does_not_duplicate(): void
    {
        $fields = Field::factory()->count(2)->create();
        $ids = $fields->pluck('id');

        $this->seed(FieldDataSeeder::class);
        $first = FieldData::whereIn('field_id', $ids)->count();

        $this->seed(FieldDataSeeder::class);

        $this->assertSame($first, FieldData::whereIn('field_id', $ids)->count());
    }
}
