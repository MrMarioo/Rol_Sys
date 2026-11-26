<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateApiTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_token_that_authenticates_api_requests(): void
    {
        $user = User::factory()->create(['email' => 'farmer@example.com']);

        $this->artisan('api:token', ['email' => 'farmer@example.com'])
            ->expectsOutputToContain('Token for farmer@example.com')
            ->assertSuccessful();

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_it_fails_for_unknown_user(): void
    {
        $this->artisan('api:token', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
