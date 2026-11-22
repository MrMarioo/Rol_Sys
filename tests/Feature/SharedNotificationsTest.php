<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SharedNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_prop_contains_unread_count_and_latest(): void
    {
        $user = User::factory()->create();
        $this->createNotifications($user, 3);
        $this->createNotifications($user, 1, read: true);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.unread_count', 3)
                ->has('notifications.latest', 4, fn (AssertableInertia $item) => $item
                    ->hasAll(['id', 'title', 'message', 'level', 'url', 'read', 'created_at'])
                )
            );
    }

    public function test_latest_is_capped_at_ten(): void
    {
        $user = User::factory()->create();
        $this->createNotifications($user, 12);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.unread_count', 12)
                ->has('notifications.latest', 10)
            );
    }

    public function test_only_own_notifications_are_shared(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->createNotifications($other, 5);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.unread_count', 0)
                ->has('notifications.latest', 0)
            );
    }

    public function test_legacy_notification_without_shape_gets_defaults(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\Legacy',
            'data' => ['message' => 'Stare powiadomienie'],
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.latest.0.title', '')
                ->where('notifications.latest.0.message', 'Stare powiadomienie')
                ->where('notifications.latest.0.level', 'info')
                ->where('notifications.latest.0.url', null)
                ->where('notifications.latest.0.read', false)
            );
    }

    public function test_guest_gets_null_notifications_prop(): void
    {
        $this->get('/login')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('notifications', null));
    }

    private function createNotifications(User $user, int $count, bool $read = false): void
    {
        for ($i = 0; $i < $count; $i++) {
            $user->notifications()->create([
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\ReportGenerated',
                'data' => [
                    'title' => 'Raport wygenerowany',
                    'message' => 'Raport gotowy.',
                    'level' => 'success',
                    'url' => '/reports/1',
                ],
                'read_at' => $read ? now() : null,
            ]);
        }
    }
}
