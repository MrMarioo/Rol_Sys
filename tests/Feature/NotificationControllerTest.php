<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->notification($user);
        $this->notification($user, read: true);
        $this->notification($other);

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Notifications/Index')
                ->has('history.data', 2)
            );
    }

    public function test_index_keeps_shared_notification_summary(): void
    {
        $user = User::factory()->create();
        $this->notification($user);
        $this->notification($user);

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.unread_count', 2)
                ->has('notifications.latest', 2)
            );
    }

    public function test_index_filters_unread(): void
    {
        $user = User::factory()->create();
        $unread = $this->notification($user);
        $this->notification($user, read: true);

        $this->actingAs($user)->get(route('notifications.index', ['filter' => ['status' => 'unread']]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('history.data', 1)
                ->where('history.data.0.id', $unread->id)
            );
    }

    public function test_read_marks_notification_and_redirects_to_url(): void
    {
        $user = User::factory()->create();
        $notification = $this->notification($user, url: '/reports/7');

        $this->actingAs($user)->post(route('notifications.read', $notification->id))
            ->assertRedirect('/reports/7');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_read_without_url_redirects_back(): void
    {
        $user = User::factory()->create();
        $notification = $this->notification($user, url: null);

        $this->actingAs($user)->from('/fields')->post(route('notifications.read', $notification->id))
            ->assertRedirect('/fields');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_read_with_external_url_redirects_back(): void
    {
        $user = User::factory()->create();
        $notification = $this->notification($user, url: '//evil.example.com/phish');

        $this->actingAs($user)->from('/fields')->post(route('notifications.read', $notification->id))
            ->assertRedirect('/fields');
    }

    public function test_cannot_read_foreign_notification(): void
    {
        $user = User::factory()->create();
        $foreign = $this->notification(User::factory()->create());

        $this->actingAs($user)->post(route('notifications.read', $foreign->id))
            ->assertNotFound();

        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_read_all_marks_only_own(): void
    {
        $user = User::factory()->create();
        $own = $this->notification($user);
        $foreign = $this->notification(User::factory()->create());

        $this->actingAs($user)->from('/fields')->post(route('notifications.read-all'))
            ->assertRedirect('/fields');

        $this->assertNotNull($own->fresh()->read_at);
        $this->assertNull($foreign->fresh()->read_at);
    }

    private function notification(User $user, bool $read = false, ?string $url = '/reports/1'): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\ReportGenerated',
            'data' => array_filter([
                'title' => 'Report generated',
                'message' => 'Report is ready.',
                'level' => 'success',
                'url' => $url,
            ]),
            'read_at' => $read ? now() : null,
        ]);
    }
}
