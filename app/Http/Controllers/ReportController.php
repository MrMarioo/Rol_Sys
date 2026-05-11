<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Http\Resources\FieldResource;
use App\Http\Resources\ReportResource;
use App\Models\Crop;
use App\Models\Field;
use App\Models\Report;
use App\Services\ReportGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly Report $report,
        private readonly ReportGenerationService $generator,
    ) {}

    public function index(Request $request): Response
    {
        $reports = QueryBuilder::for(subject: Report::class)
            ->with(relations: ['user'])
            ->where('user_id', Auth::id())
            ->allowedFilters(filters: [
                AllowedFilter::exact(name: 'type'),
                AllowedFilter::exact(name: 'status'),
                'title',
            ])
            ->allowedSorts(sorts: ['created_at', 'title', 'type'])
            ->defaultSort(sorts: ['-created_at'])
            ->paginate(
                perPage: $request->integer(key: 'per_page', default: config('pagination.list')),
            )
            ->withQueryString();

        return Inertia::render(
            component: 'Reports/Index',
            props: [
                'reports' => ReportResource::collection(resource: $reports),
                'templates' => $this->availableTemplates(),
            ]
        );
    }

    public function create(Request $request): Response
    {
        $fields = Field::where('user_id', Auth::id())->get();

        return Inertia::render(
            component: 'Reports/CreateForm',
            props: [
                'selectedTemplate' => $request->string(key: 'template', default: 'weekly_summary')->toString(),
                'templates' => $this->availableTemplates(),
                'fields' => FieldResource::collection(resource: $fields),
                'crops' => Crop::all(),
            ]
        );
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        /** @var Report $report */
        $report = $this->report->create([
            ...$request->validated(),
            'user_id' => Auth::id(),
            'status' => 'draft',
        ]);

        return redirect()
            ->route(route: 'reports.show', parameters: $report->id)
            ->with(
                key: 'success',
                value: sprintf('Report %s has been created. Click "Generate" to build the content.', $report->title),
            );
    }

    public function show(Report $report): Response
    {
        $this->authorize('view', $report);

        $report->load('user');

        return Inertia::render(
            component: 'Reports/Show',
            props: [
                'report' => ReportResource::make(resource: $report)->resolve(),
            ]
        );
    }

    public function edit(Report $report): Response
    {
        $this->authorize('update', $report);

        $fields = Field::where('user_id', Auth::id())->get();

        return Inertia::render(
            component: 'Reports/Edit',
            props: [
                'report' => ReportResource::make(resource: $report)->resolve(),
                'templates' => $this->availableTemplates(),
                'fields' => FieldResource::collection(resource: $fields),
                'crops' => Crop::all(),
            ]
        );
    }

    public function update(UpdateReportRequest $request, Report $report): RedirectResponse
    {
        $report->update($request->validated());

        return redirect()
            ->route(route: 'reports.show', parameters: $report->id)
            ->with(
                key: 'success',
                value: sprintf('Report %s has been updated.', $report->title),
            );
    }

    public function destroy(Report $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        $title = $report->title;
        $report->delete();

        return redirect()
            ->route(route: 'reports.index')
            ->with(
                key: 'success',
                value: sprintf('Report %s has been deleted.', $title),
            );
    }

    public function generate(Report $report): RedirectResponse
    {
        $this->authorize('generate', $report);

        try {
            $content = $this->generator->generate($report);
            $report->update([
                'content' => $content,
                'status' => 'generated',
            ]);

            return redirect()
                ->route(route: 'reports.show', parameters: $report->id)
                ->with(
                    key: 'success',
                    value: sprintf('Report %s generated successfully.', $report->title),
                );
        } catch (\Throwable $e) {
            return redirect()
                ->route(route: 'reports.show', parameters: $report->id)
                ->with(
                    key: 'error',
                    value: sprintf('Failed to generate report: %s', $e->getMessage()),
                );
        }
    }

    public function download(Report $report): BinaryFileResponse|StreamedResponse|HttpResponse
    {
        $this->authorize('download', $report);

        $html = view('reports-pdf', compact('report'))->render();
        $pdf = Pdf::loadHTML($html);

        return $pdf->download(sprintf('report-%d-%s.pdf', $report->id, now()->format('Y-m-d')));
    }

    private function availableTemplates(): array
    {
        return [
            'weekly_summary' => [
                'name' => 'Weekly Summary',
                'description' => 'Weekly overview of field conditions and activities',
                'icon' => 'ni-calendar-alt',
                'parameters' => ['date_range', 'fields'],
            ],
            'monthly_summary' => [
                'name' => 'Monthly Summary',
                'description' => 'Comprehensive monthly report with trends and analytics',
                'icon' => 'ni-calendar',
                'parameters' => ['month', 'year', 'fields'],
            ],
            'problem_report' => [
                'name' => 'Problem Report',
                'description' => 'Issues and anomalies detected in your fields',
                'icon' => 'ni-alert-circle',
                'parameters' => ['severity', 'date_range', 'fields'],
            ],
            'crop_status' => [
                'name' => 'Crop Status Report',
                'description' => 'Current status of crops and growth stages',
                'icon' => 'ni-growth',
                'parameters' => ['crops', 'fields', 'growth_stage'],
            ],
            'comparative' => [
                'name' => 'Comparative Analysis',
                'description' => 'Compare fields or time periods performance',
                'icon' => 'ni-bar-chart',
                'parameters' => ['comparison_type', 'fields', 'date_ranges'],
            ],
        ];
    }
}