<?php

namespace App\Http\Controllers;

use App\Enums\Sensitivity;
use App\Http\Requests\AnalyzeFieldRequest;
use App\Http\Resources\AnalyticsResource;
use App\Http\Resources\FieldResource;
use App\Models\Analytics;
use App\Models\Field;
use App\Services\FieldAnalysisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly FieldAnalysisService $analysisService,
    ) {}

    public function index(Request $request): Response
    {
        $analytics = QueryBuilder::for(subject: Analytics::class)
            ->allowedFilters(filters: [
                AllowedFilter::exact(name: 'field_id'),
                AllowedFilter::exact(name: 'analysis_type'),
            ])
            ->allowedSorts(sorts: ['analysis_date', 'created_at'])
            ->defaultSort(sorts: ['-analysis_date'])
            ->with(relations: ['field'])
            ->ownedBy(user: $request->user())
            ->paginate(
                perPage: $this->perPage(request: $request, default: 10),
            )
            ->withQueryString();

        return Inertia::render(
            component: 'Analytics/Index',
            props: [
                'analytics' => $analytics->through(fn (Analytics $analysis) => AnalyticsResource::make($analysis)->resolve()),
                'fields' => FieldResource::collection(resource: $request->user()->fields()->orderBy('name')->get())->resolve(),
                'sensitivities' => Sensitivity::values(),
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
            'sensitivity' => $request->input(key: 'sensitivity', default: Sensitivity::Medium->value),
            'include_growth_prediction' => $request->boolean(key: 'include_growth_prediction'),
            'prediction_days' => $request->integer(key: 'prediction_days', default: 7),
        ];

        try {
            $analytics = $this->analysisService->analyze(field: $field, parameters: $parameters);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->with(
                    key: 'error',
                    value: sprintf('Error: analysis of field %s failed, please try again later.', $field->name),
                );
        }

        return redirect()
            ->route(route: 'analytics.show', parameters: $analytics->id)
            ->with(
                key: 'success',
                value: sprintf('Analysis for field %s has been completed.', $field->name),
            );
    }

    public function download(Analytics $analytics): HttpResponse
    {
        $this->authorize('download', $analytics);

        return Pdf::loadView(view: 'analytics-pdf', data: ['analytics' => $analytics->load('field')])
            ->download(filename: sprintf('analysis-%d-%s.pdf', $analytics->id, $analytics->analysis_date->format('Y-m-d')));
    }

    public function destroy(Analytics $analytics): RedirectResponse
    {
        $this->authorize('delete', $analytics);

        $analytics->delete();

        return redirect()
            ->route(route: 'analytics.index')
            ->with(
                key: 'success',
                value: sprintf('Analysis of field %s has been deleted.', $analytics->field->name),
            );
    }
}
