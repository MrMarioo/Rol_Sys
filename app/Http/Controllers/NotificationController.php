<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = QueryBuilder::for(subject: $request->user()->notifications())
            ->allowedFilters(filters: [
                AllowedFilter::callback(
                    name: 'status',
                    callback: fn (Builder $query, string $value) => match ($value) {
                        'unread' => $query->whereNull('read_at'),
                        'read' => $query->whereNotNull('read_at'),
                        default => $query,
                    },
                ),
            ])
            ->allowedSorts(sorts: ['created_at'])
            ->defaultSort(sorts: ['-created_at'])
            ->paginate(
                perPage: $this->perPage(request: $request),
            )
            ->withQueryString();

        return Inertia::render(
            component: 'Notifications/Index',
            props: [
                'history' => $notifications->through(
                    fn (DatabaseNotification $notification) => NotificationResource::make($notification)->resolve(),
                ),
            ]
        );
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        /** @var DatabaseNotification $model */
        $model = $request->user()->notifications()->findOrFail($notification);
        $model->markAsRead();

        $url = $model->data['url'] ?? null;

        return $this->isInternalPath(url: $url)
            ? redirect()->to(path: $url)
            : redirect()->back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $unread = $request->user()->unreadNotifications;
        $unread->markAsRead();

        return redirect()
            ->back()
            ->with(
                key: 'success',
                value: sprintf('%d notifications have been marked as read.', $unread->count()),
            );
    }

    private function isInternalPath(?string $url): bool
    {
        return is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//');
    }
}
