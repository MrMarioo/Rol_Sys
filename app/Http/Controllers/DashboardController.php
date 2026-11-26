<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnalyticsResource;
use App\Http\Resources\FieldDataResource;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $dashboardStats,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render(
            component: 'Dashboard/Index',
            props: [
                'stats' => $this->dashboardStats->stats(user: $user),
                'fields' => $this->dashboardStats->fieldsWithActiveCrop(user: $user),
                'crops' => $this->dashboardStats->crops(),
                'fieldData' => FieldDataResource::collection(resource: $this->dashboardStats->recentFieldData(user: $user))->resolve(),
                'analytics' => AnalyticsResource::collection(resource: $this->dashboardStats->recentAnalytics(user: $user))->resolve(),
            ]
        );
    }
}
