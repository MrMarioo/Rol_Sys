<?php

namespace App\Services;

use App\Enums\FieldDataType;
use App\Enums\NdviStatus;
use App\Enums\ReportType;
use App\Models\Analytics;
use App\Models\Crop;
use App\Models\Field;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class ReportGenerationService
{
    public function generate(Report $report): array
    {
        $fieldIds = $report->fields_included ?? [];
        $parameters = $report->parameters ?? [];
        $ownerId = $report->user_id;

        return match (ReportType::from($report->type)) {
            ReportType::WeeklySummary => $this->weeklySummary($ownerId, $fieldIds, $parameters),
            ReportType::MonthlySummary => $this->monthlySummary($ownerId, $fieldIds, $parameters),
            ReportType::ProblemReport => $this->problemReport($ownerId, $fieldIds, $parameters),
            ReportType::CropStatus => $this->cropStatusReport($ownerId, $fieldIds),
            ReportType::Comparative => $this->comparativeReport($ownerId, $fieldIds, $parameters),
        };
    }

    /** @return Builder<Field> */
    private function ownedFields(int $ownerId, array $fieldIds): Builder
    {
        return Field::query()
            ->where('user_id', $ownerId)
            ->whereIn('id', $fieldIds);
    }

    /** @return Collection<int, Field> */
    private function fieldsWithPeriodData(int $ownerId, array $fieldIds, Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->ownedFields($ownerId, $fieldIds)
            ->with([
                'fieldData' => fn ($query) => $query->whereBetween('collection_date', [$startDate, $endDate]),
                'analytics' => fn ($query) => $query
                    ->whereBetween('analysis_date', [$startDate, $endDate])
                    ->orderByDesc('analysis_date'),
            ])
            ->get();
    }

    private function weeklySummary(int $ownerId, array $fieldIds, array $parameters): array
    {
        $startDate = Carbon::parse($parameters['start_date'] ?? now()->subWeek())->startOfDay();
        $endDate = Carbon::parse($parameters['end_date'] ?? now())->endOfDay();

        return $this->periodSummary($ownerId, $fieldIds, $startDate, $endDate, ReportType::WeeklySummary->label());
    }

    private function monthlySummary(int $ownerId, array $fieldIds, array $parameters): array
    {
        $month = (int) ($parameters['month'] ?? now()->month);
        $year = (int) ($parameters['year'] ?? now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        return $this->periodSummary($ownerId, $fieldIds, $startDate, $endDate, ReportType::MonthlySummary->label());
    }

    private function periodSummary(int $ownerId, array $fieldIds, Carbon $startDate, Carbon $endDate, string $type): array
    {
        $summary = [];

        foreach ($this->fieldsWithPeriodData($ownerId, $fieldIds, $startDate, $endDate) as $field) {
            $metrics = $this->fieldMetrics($field);
            $latestAnalysis = $field->analytics->first();

            $summary[$field->id] = [
                ...$metrics,
                'issues_detected' => $this->issueCount($latestAnalysis),
                'recommendations' => is_array($latestAnalysis?->recommendations) ? $latestAnalysis->recommendations : [],
            ];
        }

        return [
            'period' => $this->period($startDate, $endDate, $type),
            'summary' => $summary,
            'totals' => $this->summaryTotals($summary),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function problemReport(int $ownerId, array $fieldIds, array $parameters): array
    {
        $severity = $parameters['severity'] ?? 'all';
        $startDate = Carbon::parse($parameters['start_date'] ?? now()->subWeek())->startOfDay();
        $endDate = Carbon::parse($parameters['end_date'] ?? now())->endOfDay();

        $problems = [];

        foreach ($this->fieldsWithPeriodData($ownerId, $fieldIds, $startDate, $endDate) as $field) {
            $fieldProblems = $this->problemsForField($field->analytics, $severity);

            if ($fieldProblems !== []) {
                $problems[$field->id] = [
                    'field_name' => $field->name,
                    'problems' => $fieldProblems,
                    'problem_count' => count($fieldProblems),
                ];
            }
        }

        return [
            'period' => $this->period($startDate, $endDate, ReportType::ProblemReport->label()),
            'severity_filter' => $severity,
            'problems' => $problems,
            'total_problems' => array_sum(array_column($problems, 'problem_count')),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /** @param Collection<int, Analytics> $analytics */
    private function problemsForField(Collection $analytics, string $severity): array
    {
        $problems = [];

        foreach ($analytics as $analysis) {
            $anomalies = $analysis->results['anomalies'] ?? [];

            if (! is_array($anomalies)) {
                continue;
            }

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

    private function cropStatusReport(int $ownerId, array $fieldIds): array
    {
        /** @var Collection<int, Field> $fields */
        $fields = $this->ownedFields($ownerId, $fieldIds)
            ->with([
                'crops' => fn ($query) => $query->wherePivot('status', 'active'),
                'latestNdviReading',
            ])
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
                'healthy_crops' => count(array_filter(
                    $cropStatus,
                    fn (array $entry) => NdviStatus::from($entry['health_status'])->isHealthy(),
                )),
                'problem_crops' => count(array_filter(
                    $cropStatus,
                    fn (array $entry) => $entry['health_status'] === NdviStatus::Poor->value,
                )),
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

        $latestNdvi = $field->latestNdviReading
            ? $this->averageOfValues(new Collection([$field->latestNdviReading]), FieldDataType::Ndvi, 'ndvi_values')
            : null;

        return [
            'field_id' => $field->id,
            'field_name' => $field->name,
            'crop_name' => $crop->name,
            'planting_date' => $plantingDate->format('Y-m-d'),
            'expected_harvest' => $expectedHarvest->format('Y-m-d'),
            'days_from_planting' => $daysFromPlanting,
            'days_to_harvest' => $daysToHarvest,
            'growth_stage' => $this->growthStage($daysFromPlanting),
            'health_status' => NdviStatus::fromAverage($latestNdvi)->value,
            'expected_yield' => $crop->pivot->yield,
            'status' => $crop->pivot->status,
        ];
    }

    private function comparativeReport(int $ownerId, array $fieldIds, array $parameters): array
    {
        $comparisonType = ($parameters['comparison_type'] ?? 'fields') === 'time_periods' ? 'time_periods' : 'fields';
        $endDate = Carbon::parse($parameters['end_date'] ?? now())->endOfDay();
        $startDate = Carbon::parse($parameters['start_date'] ?? $endDate->copy()->subDays(29))->startOfDay();

        $content = [
            'comparison_type' => $comparisonType,
            'period' => $this->period($startDate, $endDate, ReportType::Comparative->label()),
        ];

        if ($comparisonType === 'fields') {
            $content['fields'] = $this->fieldsWithPeriodData($ownerId, $fieldIds, $startDate, $endDate)
                ->map(fn (Field $field) => [
                    ...$this->fieldMetrics($field),
                    'issues_detected' => $field->analytics->sum(fn (Analytics $analysis) => $this->issueCount($analysis)),
                ])
                ->values()
                ->all();
        } else {
            $periodLength = (int) $startDate->diffInDays($endDate) + 1;
            $previousEnd = $startDate->copy()->subDay()->endOfDay();
            $previousStart = $previousEnd->copy()->subDays($periodLength - 1)->startOfDay();

            $content['periods'] = [
                'current' => $this->periodMetrics($ownerId, $fieldIds, $startDate, $endDate),
                'previous' => $this->periodMetrics($ownerId, $fieldIds, $previousStart, $previousEnd),
            ];
        }

        $content['generated_at'] = now()->toIso8601String();

        return $content;
    }

    private function periodMetrics(int $ownerId, array $fieldIds, Carbon $startDate, Carbon $endDate): array
    {
        $fields = $this->fieldsWithPeriodData($ownerId, $fieldIds, $startDate, $endDate);
        $readings = new Collection($fields->flatMap(fn (Field $field) => $field->fieldData)->all());

        return [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
            'data_collections' => $readings->count(),
            'avg_ndvi' => $this->averageOfValues($readings, FieldDataType::Ndvi, 'ndvi_values'),
            'avg_moisture' => $this->averageOfValues($readings, FieldDataType::SoilMoisture, 'moisture_values'),
            'issues_detected' => $fields->sum(
                fn (Field $field) => $field->analytics->sum(fn (Analytics $analysis) => $this->issueCount($analysis)),
            ),
        ];
    }

    private function fieldMetrics(Field $field): array
    {
        $averageNdvi = $this->averageOfValues($field->fieldData, FieldDataType::Ndvi, 'ndvi_values');

        return [
            'field_id' => $field->id,
            'field_name' => $field->name,
            'field_size' => $field->size,
            'data_collections' => $field->fieldData->count(),
            'avg_ndvi' => $averageNdvi,
            'avg_moisture' => $this->averageOfValues($field->fieldData, FieldDataType::SoilMoisture, 'moisture_values'),
            'status' => NdviStatus::fromAverage($averageNdvi)->value,
        ];
    }

    private function period(Carbon $startDate, Carbon $endDate, string $type): array
    {
        return [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
            'type' => $type,
        ];
    }

    private function averageOfValues(Collection $fieldData, FieldDataType $dataType, string $valuesKey): ?float
    {
        $total = 0.0;
        $count = 0;

        foreach ($fieldData->where('data_type', $dataType->value) as $entry) {
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
        $anomalies = $analytics?->results['anomalies'] ?? [];

        return is_array($anomalies) ? count($anomalies) : 0;
    }

    private function summaryTotals(array $summary): array
    {
        $ndviValues = array_filter(array_column($summary, 'avg_ndvi'), fn (?float $value) => $value !== null);

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
}
