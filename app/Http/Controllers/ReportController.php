<?php

namespace App\Http\Controllers;

use App\Models\Analytics;
use App\Models\Crop;
use App\Models\Field;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(): Response
    {
        $reports = Report::with('user')->where('user_id', Auth::id())->orderBy('created_at', 'desc')->paginate(config('pagination.list'))->withQueryString();

        $templates = $this->getAvailableTemplates();

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'templates' => $templates,
        ]);
    }

    public function create(Request $request): Response
    {
        $templateType = $request->get('template', 'weekly_summary');
        $templates = $this->getAvailableTemplates();
        $fields = Field::where('user_id', Auth::id())->get();
        $crops = Crop::all();

        return Inertia::render('Reports/CreateForm', [
            'selectedTemplate' => $templateType,
            'templates' => $templates,
            'fields' => $fields,
            'crops' => $crops,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'description' => 'nullable|string',
            'fields_included' => 'required|array',
            'parameters' => 'required|array',
            'content' => 'nullable|array',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'draft';

        if (!isset($validated['content'])) {
            $validated['content'] = [];
        }

        $report = Report::create($validated);

        return redirect()->route('reports.generate', $report->id)
            ->with('success', 'Report created successfully. Generating content...');
    }

    public function show(Report $report): Response
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        $report->load('user');

        return Inertia::render('Reports/Show', [
            'report' => $report,
        ]);
    }

    public function edit(Report $report): Response
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        $templates = $this->getAvailableTemplates();
        $fields = Field::where('user_id', Auth::id())->get();
        $crops = Crop::all();

        return Inertia::render('Reports/Edit', [
            'report' => $report,
            'templates' => $templates,
            'fields' => $fields,
            'crops' => $crops,
        ]);
    }

    public function update(Request $request, Report $report)
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'description' => 'nullable|string',
            'fields_included' => 'required|array',
            'parameters' => 'required|array',
        ]);

        $report->update($validated);

        return redirect()->route('reports.show', $report->id)
            ->with('success', 'Report updated successfully.');
    }

    public function destroy(Report $report)
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Report deleted successfully.');
    }

    public function generate(Report $report)
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $content = $this->generateReportContent($report);

            $report->update([
                'content' => $content,
                'status' => 'generated',
            ]);

            return redirect()->route('reports.show', $report->id)
                ->with('success', 'Report generated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('reports.show', $report->id)
                ->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    public function download(Report $report)
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        $html = view('reports-pdf', compact('report'))->render();

        $pdf = Pdf::loadHTML($html);

        $filename = 'report-' . $report->id . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function getAvailableTemplates(): array
    {
        return [
            'weekly_summary' => [
                'name' => 'Weekly Summary',
                'description' => 'Weekly overview of field conditions and activities',
                'icon' => 'ni-calendar-alt',
                'parameters' => ['date_range', 'fields']
            ],
            'monthly_summary' => [
                'name' => 'Monthly Summary',
                'description' => 'Comprehensive monthly report with trends and analytics',
                'icon' => 'ni-calendar',
                'parameters' => ['month', 'year', 'fields']
            ],
            'problem_report' => [
                'name' => 'Problem Report',
                'description' => 'Issues and anomalies detected in your fields',
                'icon' => 'ni-alert-circle',
                'parameters' => ['severity', 'date_range', 'fields']
            ],
            'crop_status' => [
                'name' => 'Crop Status Report',
                'description' => 'Current status of crops and growth stages',
                'icon' => 'ni-growth',
                'parameters' => ['crops', 'fields', 'growth_stage']
            ],
            'comparative' => [
                'name' => 'Comparative Analysis',
                'description' => 'Compare fields or time periods performance',
                'icon' => 'ni-bar-chart',
                'parameters' => ['comparison_type', 'fields', 'date_ranges']
            ]
        ];
    }

    private function generateReportContent(Report $report): array
    {
        $fieldIds = $report->fields_included;
        $parameters = $report->parameters;

        switch ($report->type) {
            case 'weekly_summary':
                return $this->generateWeeklySummary($fieldIds, $parameters);
            case 'monthly_summary':
                return $this->generateMonthlySummary($fieldIds, $parameters);
            case 'problem_report':
                return $this->generateProblemReport($fieldIds, $parameters);
            case 'crop_status':
                return $this->generateCropStatusReport($fieldIds, $parameters);
            case 'comparative':
                return $this->generateComparativeReport($fieldIds, $parameters);
            default:
                throw new \Exception('Unknown report type');
        }
    }

    private function generateWeeklySummary(array $fieldIds, array $parameters): array
    {
        $startDate = Carbon::parse($parameters['start_date'] ?? now()->subWeek());
        $endDate = Carbon::parse($parameters['end_date'] ?? now());

        $fields = Field::whereIn('id', $fieldIds)->get();
        $summary = [];

        foreach ($fields as $field) {
            // Get field data for the week
            $fieldData = FieldData::where('field_id', $field->id)
                ->whereBetween('collection_date', [$startDate, $endDate])
                ->get();

            // Get analytics for the week
            $analytics = Analytics::where('field_id', $field->id)
                ->whereBetween('analysis_date', [$startDate, $endDate])
                ->latest()
                ->first();

            $summary[$field->id] = [
                'field_name' => $field->name,
                'field_size' => $field->size,
                'data_collections' => $fieldData->count(),
                'avg_ndvi' => $this->calculateAverageNdvi($fieldData),
                'avg_moisture' => $this->calculateAverageMoisture($fieldData),
                'issues_detected' => $this->getIssuesCount($fieldData),
                'recommendations' => $analytics->recommendations ?? [],
                'status' => $this->determineFieldStatus($fieldData)
            ];
        }

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'type' => 'Weekly Summary'
            ],
            'summary' => $summary,
            'totals' => $this->calculateTotals($summary),
            'generated_at' => now()->toISOString()
        ];
    }

    private function generateMonthlySummary(array $fieldIds, array $parameters): array
    {
        $month = $parameters['month'] ?? now()->month;
        $year = $parameters['year'] ?? now()->year;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        return $this->generatePeriodSummary($fieldIds, $startDate, $endDate, 'Monthly Summary');
    }

    private function generateProblemReport(array $fieldIds, array $parameters): array
    {
        $severity = $parameters['severity'] ?? 'all';
        $startDate = Carbon::parse($parameters['start_date'] ?? now()->subWeek());
        $endDate = Carbon::parse($parameters['end_date'] ?? now());

        $fields = Field::whereIn('id', $fieldIds)->get();
        $problems = [];

        foreach ($fields as $field) {
            $analytics = Analytics::where('field_id', $field->id)
                ->whereBetween('analysis_date', [$startDate, $endDate])
                ->get();

            $fieldProblems = [];
            foreach ($analytics as $analysis) {
                if (isset($analysis->results['anomalies'])) {
                    foreach ($analysis->results['anomalies'] as $anomaly) {
                        if ($severity === 'all' || $anomaly['severity'] === $severity) {
                            $fieldProblems[] = [
                                'date' => $analysis->analysis_date->format('Y-m-d'),
                                'type' => $anomaly['type'],
                                'description' => $anomaly['description'],
                                'severity' => $anomaly['severity'],
                                'location' => $anomaly['location'] ?? 'General'
                            ];
                        }
                    }
                }
            }

            if (!empty($fieldProblems)) {
                $problems[$field->id] = [
                    'field_name' => $field->name,
                    'problems' => $fieldProblems,
                    'problem_count' => count($fieldProblems)
                ];
            }
        }

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'type' => 'Problem Report'
            ],
            'severity_filter' => $severity,
            'problems' => $problems,
            'total_problems' => array_sum(array_column($problems, 'problem_count')),
            'generated_at' => now()->toISOString()
        ];
    }

    private function generateCropStatusReport(array $fieldIds, array $parameters): array
    {
        $fields = Field::with(['crops' => function($query) {
            $query->where('status', 'active');
        }])->whereIn('id', $fieldIds)->get();

        $cropStatus = [];

        foreach ($fields as $field) {
            foreach ($field->crops as $fieldCrop) {
                $crop = $fieldCrop->crop;
                $plantingDate = Carbon::parse($fieldCrop->pivot->planting_date);
                $expectedHarvest = Carbon::parse($fieldCrop->pivot->expected_harvest_date);

                $daysFromPlanting = $plantingDate->diffInDays(now());
                $daysToHarvest = now()->diffInDays($expectedHarvest);

                // Get latest field data
                $latestData = FieldData::where('field_id', $field->id)
                    ->latest('collection_date')
                    ->first();

                $cropStatus[] = [
                    'field_id' => $field->id,
                    'field_name' => $field->name,
                    'crop_name' => $crop->name,
                    'planting_date' => $plantingDate->format('Y-m-d'),
                    'expected_harvest' => $expectedHarvest->format('Y-m-d'),
                    'days_from_planting' => $daysFromPlanting,
                    'days_to_harvest' => $daysToHarvest,
                    'growth_stage' => $this->determineGrowthStage($daysFromPlanting, $crop),
                    'health_status' => $this->determineHealthStatus($latestData),
                    'expected_yield' => $fieldCrop->pivot->yield,
                    'status' => $fieldCrop->pivot->status
                ];
            }
        }

        return [
            'crop_status' => $cropStatus,
            'summary' => [
                'total_crops' => count($cropStatus),
                'healthy_crops' => count(array_filter($cropStatus, fn($c) => $c['health_status'] === 'good')),
                'problem_crops' => count(array_filter($cropStatus, fn($c) => $c['health_status'] === 'poor'))
            ],
            'generated_at' => now()->toISOString()
        ];
    }

    private function generateComparativeReport(array $fieldIds, array $parameters): array
    {
        $comparisonType = $parameters['comparison_type'] ?? 'fields';

        if ($comparisonType === 'fields') {
            return $this->compareFields($fieldIds, $parameters);
        } else {
            return $this->compareTimePeriods($fieldIds, $parameters);
        }
    }

    // Helper methods
    private function calculateAverageNdvi($fieldData)
    {
        $ndviData = $fieldData->where('data_type', 'ndvi');
        if ($ndviData->isEmpty()) return null;

        $totalNdvi = 0;
        $count = 0;

        foreach ($ndviData as $data) {
            if (isset($data->data['ndvi_values'])) {
                $totalNdvi += array_sum($data->data['ndvi_values']);
                $count += count($data->data['ndvi_values']);
            }
        }

        return $count > 0 ? round($totalNdvi / $count, 3) : null;
    }

    private function calculateAverageMoisture($fieldData)
    {
        $moistureData = $fieldData->where('data_type', 'soil_moisture');
        if ($moistureData->isEmpty()) return null;

        $totalMoisture = 0;
        $count = 0;

        foreach ($moistureData as $data) {
            if (isset($data->data['moisture_values'])) {
                $totalMoisture += array_sum($data->data['moisture_values']);
                $count += count($data->data['moisture_values']);
            }
        }

        return $count > 0 ? round($totalMoisture / $count, 3) : null;
    }

    private function getIssuesCount($fieldData)
    {
        // This would integrate with your anomaly detection
        return rand(0, 3); // Placeholder
    }

    private function determineFieldStatus($fieldData)
    {
        $avgNdvi = $this->calculateAverageNdvi($fieldData);

        if ($avgNdvi === null) return 'no_data';
        if ($avgNdvi > 0.7) return 'excellent';
        if ($avgNdvi > 0.5) return 'good';
        if ($avgNdvi > 0.3) return 'fair';
        return 'poor';
    }

    private function calculateTotals($summary)
    {
        return [
            'total_fields' => count($summary),
            'total_size' => array_sum(array_column($summary, 'field_size')),
            'avg_ndvi' => round(array_sum(array_filter(array_column($summary, 'avg_ndvi'))) / count(array_filter(array_column($summary, 'avg_ndvi'))), 3),
            'total_issues' => array_sum(array_column($summary, 'issues_detected'))
        ];
    }

    private function determineGrowthStage($daysFromPlanting, $crop)
    {
        // Simplified growth stage determination
        if ($daysFromPlanting < 14) return 'germination';
        if ($daysFromPlanting < 30) return 'seedling';
        if ($daysFromPlanting < 60) return 'vegetative';
        if ($daysFromPlanting < 90) return 'flowering';
        return 'maturation';
    }

    private function determineHealthStatus($latestData)
    {
        if (!$latestData) return 'unknown';

        if ($latestData->data_type === 'ndvi' && isset($latestData->data['ndvi_values'])) {
            $avgNdvi = array_sum($latestData->data['ndvi_values']) / count($latestData->data['ndvi_values']);
            return $avgNdvi > 0.6 ? 'good' : ($avgNdvi > 0.4 ? 'fair' : 'poor');
        }

        return 'unknown';
    }

    private function generatePeriodSummary($fieldIds, $startDate, $endDate, $type)
    {
        // Reuse weekly summary logic with different date range
        $parameters = [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ];

        $content = $this->generateWeeklySummary($fieldIds, $parameters);
        $content['period']['type'] = $type;

        return $content;
    }

    private function compareFields($fieldIds, $parameters)
    {
        // Implementation for field comparison
        return [
            'comparison_type' => 'fields',
            'fields' => $fieldIds,
            'metrics' => [],
            'generated_at' => now()->toISOString()
        ];
    }

    private function compareTimePeriods($fieldIds, $parameters)
    {
        // Implementation for time period comparison
        return [
            'comparison_type' => 'time_periods',
            'periods' => $parameters['date_ranges'] ?? [],
            'metrics' => [],
            'generated_at' => now()->toISOString()
        ];
    }
}
