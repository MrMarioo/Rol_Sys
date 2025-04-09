<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Field;
use App\Models\Crop;
use App\Models\FieldData;
use App\Models\Analytics;
use App\Models\User;
use App\Models\DataSource;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = $this->getStats();
        $fields = $this->getFields();
        $crops = $this->getCrops();
        $fieldData = $this->getRecentFieldData();
        $analytics = $this->getRecentAnalytics();
        $notifications = $this->getUnreadNotifications();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'fields' => $fields,
            'crops' => $crops,
            'fieldData' => $fieldData,
            'analytics' => $analytics,
            'notifications' => $notifications,
            'user' => Auth::user(),
        ]);
    }
    private function getStats()
    {
        return [
            'totalFields' => $this->getTotalFields(),
            'activeCrops' => $this->getActiveCrops(),
            'pendingAlerts' => $this->getPendingAlerts(),
            'recentDataCollections' => $this->getRecentDataCollections(),
        ];
    }
    private function getFields(): Collection
    {
        $fields = Field::with([
            'crops' => function ($query) {
                $query
                    ->where('status', 'active')
                    ->whereNull('actual_harvest_date');
            },
        ])
            ->where('user_id', Auth::id())
            ->get();
        return $fields->map(function ($field) {
            $field->current_crop = $field->crops->first()
                ? $field->crops->first()->crop
                : null;
            unset($field->crops);
            return $field;
        });
    }
    private function getCrops(): Collection
    {
        return Crop::select('id', 'name', 'description')
            ->orderBy('name')
            ->get();
    }
    private function getRecentFieldData(): Collection
    {
        return FieldData::with('field')
            ->whereHas('field', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->select(
                'id',
                'field_id',
                'data_type',
                'collection_date',
                'latitude',
                'longitude',
            )
            ->orderBy('collection_date', 'desc')
            ->limit(10)
            ->get();
    }

    private function getRecentAnalytics(): Collection
    {
        return Analytics::with('field')
            ->whereHas('field', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->select(
                'id',
                'field_id',
                'analysis_type',
                'analysis_date',
                'recommendations',
            )
            ->orderBy('analysis_date', 'desc')
            ->limit(10)
            ->get();
    }

    private function getUnreadNotifications()
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

    private function getTotalFields()
    {
        return Field::where('user_id', Auth::id())->count();
    }

    private function getActiveCrops(): int
    {
        $user = Auth::user();

        return Field::where('user_id', $user->id)
            ->whereHas('crops', function ($query) {
                $query
                    ->where('status', 'active')
                    ->whereNull('actual_harvest_date');
            })
            ->count();
    }

    private function getPendingAlerts(): int
    {
        return Auth::user()->unreadNotifications->count();
    }

    private function getRecentDataCollections(): int
    {
        $lastWeek = Carbon::now()->subDays(7);

        return FieldData::whereHas('field', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->where('collection_date', '>=', $lastWeek->toDateString())
            ->count();
    }
}
