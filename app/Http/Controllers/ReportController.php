<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Http\Resources\FieldResource;
use App\Http\Resources\ReportResource;
use App\Models\Crop;
use App\Models\Report;
use App\Notifications\ReportGenerated;
use App\Services\ReportGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class ReportController extends Controller
{
    public function __construct(
        private readonly Report $report,
        private readonly Crop $crop,
        private readonly ReportGenerationService $generator,
    ) {}

    public function index(Request $request): Response
    {
        $reports = QueryBuilder::for(subject: Report::class)
            ->allowedFilters(filters: [
                AllowedFilter::exact(name: 'type'),
                AllowedFilter::exact(name: 'status'),
                'title',
            ])
            ->allowedSorts(sorts: ['created_at', 'title', 'type'])
            ->defaultSort(sorts: ['-created_at'])
            ->with(relations: ['user'])
            ->where('user_id', $request->user()->id)
            ->paginate(
                perPage: $this->perPage(request: $request),
            )
            ->withQueryString();

        return Inertia::render(
            component: 'Reports/Index',
            props: [
                'reports' => $reports->through(fn (Report $report) => ReportResource::make($report)->resolve()),
                'templates' => ReportType::templates(),
            ]
        );
    }

    public function create(Request $request): Response
    {
        $fields = $request->user()->fields()->orderBy('name')->get();

        return Inertia::render(
            component: 'Reports/CreateForm',
            props: [
                'selectedTemplate' => $request->string(key: 'template', default: 'weekly_summary')->toString(),
                'templates' => ReportType::templates(),
                'fields' => FieldResource::collection(resource: $fields)->resolve(),
                'crops' => $this->crop->query()->orderBy('name')->get(columns: ['id', 'name']),
            ]
        );
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        /** @var Report $report */
        $report = $this->report->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'status' => ReportStatus::Draft,
            'content' => [],
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

    public function edit(Request $request, Report $report): Response
    {
        $this->authorize('update', $report);

        $fields = $request->user()->fields()->orderBy('name')->get();

        return Inertia::render(
            component: 'Reports/Edit',
            props: [
                'report' => ReportResource::make(resource: $report)->resolve(),
                'templates' => ReportType::templates(),
                'fields' => FieldResource::collection(resource: $fields)->resolve(),
                'crops' => $this->crop->query()->orderBy('name')->get(columns: ['id', 'name']),
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
            $content = $this->generator->generate(report: $report);
            $report->update([
                'content' => $content,
                'status' => ReportStatus::Generated,
            ]);

            $report->user->notify(new ReportGenerated(report: $report));

            return redirect()
                ->route(route: 'reports.show', parameters: $report->id)
                ->with(
                    key: 'success',
                    value: sprintf('Report %s generated successfully.', $report->title),
                );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route(route: 'reports.show', parameters: $report->id)
                ->with(
                    key: 'error',
                    value: sprintf('Error: report %s could not be generated, please try again later.', $report->title),
                );
        }
    }

    public function download(Report $report): BinaryFileResponse|StreamedResponse|HttpResponse
    {
        $this->authorize('download', $report);

        return Pdf::loadView(view: 'reports-pdf', data: ['report' => $report])
            ->download(filename: sprintf('report-%d-%s.pdf', $report->id, now()->format('Y-m-d')));
    }
}
