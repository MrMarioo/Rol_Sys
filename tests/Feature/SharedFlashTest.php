<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class SharedFlashTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_error_is_shared_after_redirect(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['error' => 'AI down'])
            ->get(route('fields.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.error', 'AI down')
                ->where('flash.success', null)
            );
    }
}
