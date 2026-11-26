<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\Field;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
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

    public function test_analyze_stores_results_from_ai_service(): void
    {
        Notification::fake();
        Http::fake(['*/analyze/field/*' => Http::response(['vegetation_health' => ['avg_ndvi' => 0.6], 'recommendations' => [['action' => 'Irrigate']]])]);
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('analytics.analyze', $field), ['sensitivity' => 'high']);

        $analytics = Analytics::firstOrFail();
        $response->assertRedirect(route('analytics.show', $analytics->id));
        $this->assertSame('high', $analytics->parameters['sensitivity']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), "/analyze/field/{$field->id}"));
    }

    public function test_analyze_hides_ai_service_error_details(): void
    {
        Notification::fake();
        Http::fake(['*/analyze/field/*' => Http::response('Traceback: secret internal path /app/model.pkl', 500)]);
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();

        $this->actingAs($user)
            ->from(route('fields.show', $field))
            ->post(route('analytics.analyze', $field))
            ->assertRedirect(route('fields.show', $field))
            ->assertSessionHas('error', fn (string $message) => ! str_contains($message, 'Traceback'));

        $this->assertDatabaseCount('analytics', 0);
    }

    public function test_analyze_rejects_unknown_sensitivity_and_other_users_field(): void
    {
        $user = User::factory()->create();
        $ownField = Field::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('analytics.analyze', $ownField), ['sensitivity' => 'extreme'])
            ->assertSessionHasErrors('sensitivity');

        $this->actingAs($user)
            ->post(route('analytics.analyze', Field::factory()->create()))
            ->assertForbidden();
    }

    public function test_download_returns_real_pdf(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->for($user)->create();
        $analytics = Analytics::factory()->create(['field_id' => $field->id]);

        $response = $this->actingAs($user)->get(route('analytics.download', $analytics));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_other_user_cannot_view_download_or_delete_analysis(): void
    {
        $analytics = Analytics::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('analytics.show', $analytics))->assertForbidden();
        $this->actingAs($intruder)->get(route('analytics.download', $analytics))->assertForbidden();
        $this->actingAs($intruder)->delete(route('analytics.destroy', $analytics))->assertForbidden();
        $this->assertDatabaseHas('analytics', ['id' => $analytics->id]);
    }

    public function test_per_page_is_capped(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('analytics.index', ['per_page' => 1000000]))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('analytics.per_page', 100));
    }
}
