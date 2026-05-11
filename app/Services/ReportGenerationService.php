<?php

namespace App\Services;

use App\Models\Analytics;
use App\Models\Crop;
use App\Models\Field;
use App\Models\FieldData;
use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class ReportGenerationService
{
    public function generate(Report $report): array
    {
        $fieldIds = $report->fields_included;
        $parameters = $report->parameters;

        return match ($report->type) {
            'weekly_summary' => $this->weeklySummary($fieldIds, $parameters),
            'monthly_summary' => $this->monthlySummary($fieldIds, $parameters),
            'problem_report' => $this->problemReport($fieldIds, $parameters),
            'crop_status' => $this->cropStatusReport($fieldIds, $parameters),
            'comparative' => $this->comparativeReport($fieldIds, $parameters),
            default => throw new InvalidArgumentException(
                sprintf('Unknown report type: %s', $report->type),
            ),
        };
    }

    private function weeklySummary(array $fieldIds, array $parameters): array
    {
        $startDate = Carbon::parse($parameters['start_date'] ?? now()->subWeek());
        $endDate = Carbon::parse($parameters['end_date'] ?? now());

        return $this->periodSummary($fieldIds, $startDate, $endDate, 'Weekly Summary');
    }

    private function monthlySummary(array $fieldIds, array $parameters): array
    {
        $month = $parameters['month'] ?? now()->month;
        $year = $parameters['year'] ?? now()->year;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        return $this->periodSummary($fieldIds, $startDate, $endDate, 'Monthly Summary');
    }

    private function periodSummary(array $fieldIds, Carbon $startDate, Carbon $endDate, string $type): array
    {
        $fields = Field::whereIn('id', $fieldIds)->get();
        $summary = [];

        foreach ($fields as $field) {
            $fieldData = FieldData::where('field_id', $field->id)
                ->whereBetween('collection_date', [$startDate, $endDate])
                ->get();

            $analytics = Analytics::where('field_id', $field->id)
                ->whereBetween('analysis_date', [$startDate, $endDate])
                ->latest('analysis_date')
                ->first();

            $summary[$field->id] = [
                'field_name' => $field->name,
                'field_size' => $field->size,
                'data_collections' => $fieldData->count(),
                'avg_ndvi' => $this->averageNdvi($fieldData),
                'avg_moisture' => $this->averageMoisture($fieldData),
                'issues_detected' => $this->issueCount($analytics),
                'recommendations' => $analytics?->recommendations ?? [],
                'status' => $this->fieldStatusFromNdvi($this->averageNdvi($fieldData)),
            ];
        }

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'type' => $type,
            ],
            'summary' => $summary,
            'totals' => $this->summaryTotals($summary),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function problemReport(array $fieldIds, array $parameters): array
    {
        $severity = $parameters['severity'] ?? 'all';
        $startDate = Carbon::parse($parameters['start_date'] ?? now()->subWeek());
        $endDate = Carbon::parse($parameters['end_date'] ?? now());

        $fields = Field::whereIn('id', $fieldIds)->get();
        $problems = [];

        foreach ($fields as $field) {
            $fieldProblems = $this->problemsForField($field, $startDate, $endDate, $severity);

            if ($fieldProblems !== []) {
                $problems[$field->id] = [
                    'field_name' => $field->name,
                    'problems' => $fieldProblems,
                    'problem_count' => count($fieldProblems),
                ];
            }
        }

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'type' => 'Problem Report',
            ],
            'severity_filter' => $severity,
            'problems' => $problems,
            'total_problems' => array_sum(array_column($problems, 'problem_count')),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function problemsForField(Field $field, Carbon $startDate, Carbon $endDate, string $severity): array
    {
        $analytics = Analytics::where('field_id', $field->id)
            ->whereBetween('analysis_date', [$startDate, $endDate])
            ->get();

        $problems = [];
        foreach ($analytics as $analysis) {
            $anomalies = $analysis->results['anomalies'] ?? [];
            foreach ($anomalies as $anomaly) {
                if ($severity !== 'all' && ($anomaly['severity'] ?? null) !== $severity) {
                    continue;
                }
                $problems[] = [
                    'date' => $analysis->analysis_date->format('Y-m-d'),
                    'type' => $anomaly['type'] ?? 'unknown',
                    'description' => $anomaly['description'] ?? '',
                    'severity' => $anomaly['severity'] ?? 'unknown',
                    'location' => $anomaly['location'] ?? 'General',
                ];
            }
        }

        return $problems;
    }

    private function cropStatusReport(array $fieldIds, array $parameters): array
    {
        $fields = Field::with(['crops' => fn ($q) => $q->where('status', 'active')])
            ->whereIn('id', $fieldIds)
            ->get();

        $cropStatus = [];

        foreach ($fields as $field) {
            foreach ($field->crops as $crop) {
                $cropStatus[] = $this->cropStatusEntry($field, $crop);
            }
        }

        return [
            'crop_status' => $cropStatus,
            'summary' => [
                'total_crops' => count($cropStatus),
                'healthy_crops' => count(array_filter($cropStatus, fn ($c) => $c['health_status'] === 'good')),
                'problem_crops' => count(array_filter($cropStatus, fn ($c) => $c['health_status'] === 'poor')),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function cropStatusEntry(Field $field, Crop $crop): array
    {
        $plantingDate = Carbon::parse($crop->pivot->planting_date);
        $expectedHarvest = Carbon::parse($crop->pivot->expected_harvest_date);
        $daysFromPlanting = (int) $plantingDate->diffInDays(now());
        $daysToHarvest = (int) now()->diffInDays($expectedHarvest);

        $latestData = FieldData::where('field_id', $field->id)
            ->latest('collection_date')
            ->first();

        return [
            'field_id' => $field->id,
            'field_name' => $field->name,
            'crop_name' => $crop->name,
            'planting_date' => $plantingDate->format('Y-m-d'),
            'expected_harvest' => $expectedHarvest->format('Y-m-d'),
            'days_from_planting' => $daysFromPlanting,
            'days_to_harvest' => $daysToHarvest,
            'growth_stage' => $this->growthStage($daysFromPlanting),
            'health_status' => $this->healthStatusFromData($latestData),
            'expected_yield' => $crop->pivot->yield,
            'status' => $crop->pivot->status,
        ];
    }

    private function comparativeReport(array $fieldIds, array $parameters): array
    {
        $comparisonType = $parameters['comparison_type'] ?? 'fields';

        return [
            'comparison_type' => $comparisonType,
            'fields' => $comparisonType === 'fields' ? $fieldIds : null,
            'periods' => $comparisonType !== 'fields' ? ($parameters['date_ranges'] ?? []) : null,
            'metrics' => [],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function averageNdvi(Collection $fieldData): ?float
    {
        return $this->averageOfValues($fieldData, 'ndvi', 'ndvi_values');
    }

    private function averageMoisture(Collection $fieldData): ?float
    {
        return $this->averageOfValues($fieldData, 'soil_moisture', 'moisture_values');
    }

    private function averageOfValues(Collection $fieldData, string $dataType, string $valuesKey): ?float
    {
        $entries = $fieldData->where('data_type', $dataType);
        if ($entries->isEmpty()) {
            return null;
        }

        $total = 0.0;
        $count = 0;

        foreach ($entries as $entry) {
            $values = $entry->data[$valuesKey] ?? null;
            if (! is_array($values)) {
                continue;
            }
            $total += array_sum($values);
            $count += count($values);
        }

        return $count > 0 ? round($total / $count, 3) : null;
    }

    private function issueCount(?Analytics $analytics): int
    {
        if ($analytics === null) {
            return 0;
        }
        $anomalies = $analytics->results['anomalies'] ?? [];

        return is_array($anomalies) ? count($anomalies) : 0;
    }

    private function fieldStatusFromNdvi(?float $avgNdvi): string
    {
        if ($avgNdvi === null) {
            return 'no_data';
        }
        return match (true) {
            $avgNdvi > 0.7 => 'excellent',
            $avgNdvi > 0.5 => 'good',
            $avgNdvi > 0.3 => 'fair',
            default => 'poor',
        };
    }

    private function summaryTotals(array $summary): array
    {
        $ndviValues = array_filter(array_column($summary, 'avg_ndvi'));

        return [
            'total_fields' => count($summary),
            'total_size' => array_sum(array_column($summary, 'field_size')),
            'avg_ndvi' => count($ndviValues) > 0
                ? round(array_sum($ndviValues) / count($ndviValues), 3)
                : null,
            'total_issues' => array_sum(array_column($summary, 'issues_detected')),
        ];
    }

    private function growthStage(int $daysFromPlanting): string
    {
        return match (true) {
            $daysFromPlanting < 14 => 'germination',
            $daysFromPlanting < 30 => 'seedling',
            $daysFromPlanting < 60 => 'vegetative',
            $daysFromPlanting < 90 => 'flowering',
            default => 'maturation',
        };
    }

    private function healthStatusFromData(?FieldData $latestData): string
    {
        if ($latestData === null) {
            return 'unknown';
        }

        if ($latestData->data_type !== 'ndvi') {
            return 'unknown';
        }

        $values = $latestData->data['ndvi_values'] ?? null;
        if (! is_array($values) || $values === []) {
            return 'unknown';
        }

        $avgNdvi = array_sum($values) / count($values);

        return match (true) {
            $avgNdvi > 0.6 => 'good',
            $avgNdvi > 0.4 => 'fair',
            default => 'poor',
        };
    }
}