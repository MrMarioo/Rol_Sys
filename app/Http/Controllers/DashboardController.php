<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnalyticsResource;
use App\Http\Resources\FieldDataResource;
use App\Http\Resources\FieldResource;
use App\Models\Analytics;
use App\Models\Crop;
use App\Models\Field;
use App\Models\FieldData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render(
            component: 'Dashboard/Index',
            props: [
                'stats' => $this->stats(),
                'fields' => $this->fieldsWithActiveCrop(),
                'crops' => $this->crops(),
                'fieldData' => FieldDataResource::collection(resource: $this->recentFieldData())->resolve(),
                'analytics' => AnalyticsResource::collection(resource: $this->recentAnalytics())->resolve(),
                'notifications' => $this->unreadNotifications(),
                'user' => Auth::user(),
            ]
        );
    }

    private function stats(): array
    {
        $userId = Auth::id();

        return [
            'totalFields' => Field::where('user_id', $userId)->count(),
            'activeCrops' => $this->activeCropsCount($userId),
            'pendingAlerts' => Auth::user()->unreadNotifications->count(),
            'recentDataCollections' => $this->recentDataCollectionsCount($userId),
        ];
    }

    private function fieldsWithActiveCrop(): SupportCollection
    {
        $fields = Field::with([
            'crops' => fn ($q) => $q->where('status', 'active')->whereNull('actual_harvest_date'),
        ])
            ->where('user_id', Auth::id())
            ->get();

        return $fields->map(function (Field $field) {
            $activeCrop = $field->crops->first();
            $payload = FieldResource::make(resource: $field)->resolve();
            $payload['current_crop'] = $activeCrop?->only(['id', 'name', 'description']);

            return $payload;
        });
    }

    private function crops(): Collection
    {
        return Crop::select(['id', 'name', 'description'])->orderBy('name')->get();
    }

    private function recentFieldData(): Collection
    {
        return FieldData::with('field')
            ->whereHas('field', fn ($q) => $q->where('user_id', Auth::id()))
            ->select(['id', 'field_id', 'data_type', 'collection_date', 'latitude', 'longitude'])
            ->orderBy('collection_date', 'desc')
            ->limit(10)
            ->get();
    }

    private function recentAnalytics(): Collection
    {
        return Analytics::with('field')
            ->whereHas('field', fn ($q) => $q->where('user_id', Auth::id()))
            ->select(['id', 'field_id', 'analysis_type', 'analysis_date', 'recommendations'])
            ->orderBy('analysis_date', 'desc')
            ->limit(10)
            ->get();
    }

    private function unreadNotifications(): SupportCollection
    {
        return Auth::user()->unreadNotifications->map(function ($notification) {
            $data = is_string($notification->data)
                ? json_decode($notification->data, true)
                : (array) $notification->data;

            return [
                'id' => $notification->id,
                'type' => $data['type'] ?? 'info',
                'title' => $data['title'] ?? '',
                'message' => $data['message'] ?? '',
                'created_at' => $notification->created_at->diffForHumans(),
            ];
        });
    }

    private function activeCropsCount(int $userId): int
    {
        return Field::where('user_id', $userId)
            ->whereHas(
                'crops',
                fn ($q) => $q->where('status', 'active')->whereNull('actual_harvest_date'),
            )
            ->count();
    }

    private function recentDataCollectionsCount(int $userId): int
    {
        return FieldData::whereHas('field', fn ($q) => $q->where('user_id', $userId))
            ->where('collection_date', '>=', Carbon::now()->subDays(7)->toDateString())
            ->count();
    }
}