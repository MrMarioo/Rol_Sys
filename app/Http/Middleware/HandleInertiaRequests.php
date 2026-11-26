<?php

namespace App\Http\Middleware;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'notifications' => fn () => $this->notifications($request),
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    private function notifications(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'latest' => NotificationResource::collection(
                resource: $user->notifications()->limit(10)->get(),
            )->resolve(),
        ];
    }
}
