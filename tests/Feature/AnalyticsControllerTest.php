<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\Field;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_exposes_field_name_directly(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create(['name' => 'North']);
        Analytics::factory()->create(['field_id' => $field->id]);

        $this->actingAs($user)
            ->get(route('analytics.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('analytics.data.0.field.name', 'North'));
    }
}
