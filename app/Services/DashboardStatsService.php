<?php

namespace App\Services;

use App\Http\Resources\FieldResource;
use App\Models\Analytics;
use App\Models\Crop;
use App\Models\Field;
use App\Models\FieldData;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection as SupportCollection;

final class DashboardStatsService
{
    private const RECENT_LIMIT = 10;

    private const RECENT_COLLECTION_DAYS = 7;

    public function stats(User $user): array
    {
        return [
            'totalFields' => $user->fields()->count(),
            'activeCrops' => $user->fields()
                ->whereHas('crops', fn (Builder $query) => $this->activeCropConstraint($query))
                ->count(),
            'pendingAlerts' => $user->unreadNotifications()->count(),
            'recentDataCollections' => FieldData::query()
                ->ownedBy(user: $user)
                ->where('collection_date', '>=', now()->subDays(self::RECENT_COLLECTION_DAYS)->toDateString())
                ->count(),
        ];
    }

    public function fieldsWithActiveCrop(User $user): SupportCollection
    {
        return $user->fields()
            ->with(['crops' => fn (BelongsToMany $query) => $query
                ->wherePivot('status', 'active')
                ->wherePivotNull('actual_harvest_date')])
            ->get()
            ->map(fn (Field $field) => [
                ...FieldResource::make($field)->resolve(),
                'current_crop' => $field->crops->first()?->only(['id', 'name', 'description']),
            ]);
    }

    public function crops(): Collection
    {
        return Crop::query()->select(['id', 'name', 'description'])->orderBy('name')->get();
    }

    public function recentFieldData(User $user): Collection
    {
        return FieldData::query()
            ->with(['field'])
            ->ownedBy(user: $user)
            ->select(['id', 'field_id', 'data_type', 'collection_date', 'latitude', 'longitude'])
            ->orderByDesc('collection_date')
            ->limit(self::RECENT_LIMIT)
            ->get();
    }

    public function recentAnalytics(User $user): Collection
    {
        return Analytics::query()
            ->with(['field'])
            ->ownedBy(user: $user)
            ->select(['id', 'field_id', 'analysis_type', 'analysis_date', 'recommendations'])
            ->orderByDesc('analysis_date')
            ->limit(self::RECENT_LIMIT)
            ->get();
    }

    private function activeCropConstraint(Builder $query): Builder
    {
        return $query
            ->where('field_crops.status', 'active')
            ->whereNull('field_crops.actual_harvest_date');
    }
}
