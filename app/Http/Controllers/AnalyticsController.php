<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyzeFieldRequest;
use App\Http\Resources\AnalyticsResource;
use App\Http\Resources\FieldResource;
use App\Models\Analytics;
use App\Models\Field;
use App\Services\FieldAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly Analytics $analytics,
        private readonly FieldAnalysisService $analysisService,
    ) {}

    public function index(Request $request): Response
    {
        $analytics = QueryBuilder::for(subject: Analytics::class)
            ->with(relations: ['field'])
            ->whereHas('field', fn ($query) => $query->where('user_id', Auth::id()))
            ->allowedFilters(filters: [
                AllowedFilter::exact(name: 'field_id'),
                AllowedFilter::exact(name: 'analysis_type'),
            ])
            ->allowedSorts(sorts: ['analysis_date', 'created_at'])
            ->defaultSort(sorts: ['-analysis_date'])
            ->paginate(
                perPage: $request->integer(key: 'per_page', default: 10),
            )
            ->withQueryString();

        $fields = Field::where('user_id', Auth::id())->get();

        return Inertia::render(
            component: 'Analytics/Index',
            props: [
                'analytics' => AnalyticsResource::collection(resource: $analytics),
                'fields' => FieldResource::collection(resource: $fields),
                'sensitivities' => ['low', 'medium', 'high'],
            ]
        );
    }

    public function show(Analytics $analytics): Response
    {
        $this->authorize('view', $analytics);

        return Inertia::render(
            component: 'Analytics/Show',
            props: [
                'analytics' => AnalyticsResource::make(resource: $analytics)->resolve(),
                'field' => FieldResource::make(resource: $analytics->field)->resolve(),
            ]
        );
    }

    public function analyze(AnalyzeFieldRequest $request, Field $field): RedirectResponse
    {
        $parameters = [
            'start_date' => $request->input(key: 'start_date'),
            'end_date' => $request->input(key: 'end_date'),
            'sensitivity' => $request->input(key: 'sensitivity', default: 'medium'),
            'include_growth_prediction' => $request->boolean(key: 'include_growth_prediction'),
            'prediction_days' => $request->integer(key: 'prediction_days', default: 7),
        ];

        try {
            $analytics = $this->analysisService->analyze(field: $field, parameters: $parameters);
        } catch (\Throwable $e) {
            Log::error('Field analysis failed', [
                'field_id' => $field->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with(
                key: 'error',
                value: sprintf('Analysis failed: %s', $e->getMessage()),
            );
        }

        return redirect()
            ->route(route: 'analytics.show', parameters: $analytics->id)
            ->with(
                key: 'success',
                value: sprintf('Analysis for field %s completed.', $field->name),
            );
    }

    public function download(Analytics $analytics): HttpResponse
    {
        $this->authorize('download', $analytics);

        $body = sprintf('Analysis report for field %s', $analytics->field->name);

        return response(
            content: $body,
            status: HttpResponse::HTTP_OK,
            headers: [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf(
                    'attachment; filename=analysis-report-%d.pdf',
                    $analytics->id,
                ),
            ],
        );
    }

    public function destroy(Analytics $analytics): RedirectResponse
    {
        $this->authorize('delete', $analytics);

        $analytics->delete();

        return redirect()
            ->route(route: 'analytics.index')
            ->with(
                key: 'success',
                value: 'Analysis deleted successfully.',
            );
    }
}