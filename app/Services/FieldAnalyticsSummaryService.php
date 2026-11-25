<?php

namespace App\Services;

use App\Models\Analytics;
use App\Models\Field;
use Illuminate\Support\Str;

final class FieldAnalyticsSummaryService
{
    private const TOP_LIMIT = 3;

    private const PRIORITY_RANK = ['high' => 0, 'medium' => 1, 'low' => 2];

    private const MISSING_RANK = 3;

    private const TYPE_LABELS = [
        'standard_analysis' => 'Standard',
        'comprehensive_with_prediction' => 'With prediction',
    ];

    public function summarize(Field $field): array
    {
        $analyses = $field->analytics()
            ->select(['id', 'field_id', 'analysis_type', 'analysis_date', 'results', 'recommendations'])
            ->orderByDesc('analysis_date')
            ->orderByDesc('id')
            ->get();

        $latest = $analyses->first();
        $latestRecommendations = $latest ? $this->recommendations(analysis: $latest) : [];

        return [
            'latestAnalysis' => $latest ? $this->latestAnalysis(analysis: $latest, recommendations: $latestRecommendations) : null,
            'topRecommendations' => array_slice($latestRecommendations, 0, self::TOP_LIMIT),
            'analysisHistory' => $analyses
                ->map(fn (Analytics $analysis) => $this->historyItem(analysis: $analysis))
                ->values()
                ->all(),
        ];
    }

    private function latestAnalysis(Analytics $analysis, array $recommendations): array
    {
        $vegetation = $this->vegetation(analysis: $analysis);
        $moisture = $this->moisture(analysis: $analysis);

        return [
            'id' => $analysis->id,
            'date' => $analysis->analysis_date->toDateString(),
            'type' => $analysis->analysis_type,
            'type_label' => $this->typeLabel(type: $analysis->analysis_type),
            'vegetation' => $vegetation,
            'moisture' => $moisture,
            'health_score' => $this->healthScore(analysis: $analysis),
            'anomaly_count' => $this->anomalyCount(analysis: $analysis),
            'recommendations' => $recommendations,
        ];
    }

    private function historyItem(Analytics $analysis): array
    {
        $vegetation = $this->vegetation(analysis: $analysis);
        $moisture = $this->moisture(analysis: $analysis);

        return [
            'id' => $analysis->id,
            'date' => $analysis->analysis_date->toDateString(),
            'type_label' => $this->typeLabel(type: $analysis->analysis_type),
            'vegetation_status' => $vegetation['status'] ?? null,
            'ndvi' => $vegetation['ndvi'] ?? null,
            'moisture_status' => $moisture['status'] ?? null,
            'moisture_percent' => $moisture['percent'] ?? null,
            'health_score' => $this->healthScore(analysis: $analysis),
            'recommendation_count' => count($this->recommendations(analysis: $analysis)),
            'anomaly_count' => $this->anomalyCount(analysis: $analysis),
        ];
    }

    private function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? Str::headline($type);
    }

    private function section(Analytics $analysis, string $key): ?array
    {
        $section = is_array($analysis->results) ? ($analysis->results[$key] ?? null) : null;

        return is_array($section) && ! array_key_exists('error', $section) ? $section : null;
    }

    private function vegetation(Analytics $analysis): ?array
    {
        $section = $this->section(analysis: $analysis, key: 'vegetation_health');

        if ($section === null) {
            return null;
        }

        return [
            'status' => is_string($section['health_status'] ?? null) ? $section['health_status'] : null,
            'ndvi' => is_numeric($section['avg_ndvi'] ?? null) ? round((float) $section['avg_ndvi'], 2) : null,
        ];
    }

    private function moisture(Analytics $analysis): ?array
    {
        $section = $this->section(analysis: $analysis, key: 'soil_moisture');

        if ($section === null) {
            return null;
        }

        return [
            'status' => is_string($section['moisture_status'] ?? null) ? $section['moisture_status'] : null,
            'percent' => is_numeric($section['avg_moisture'] ?? null) ? round((float) $section['avg_moisture'] * 100, 1) : null,
        ];
    }

    private function healthScore(Analytics $analysis): ?int
    {
        $prediction = $this->section(analysis: $analysis, key: 'growth_prediction');
        $score = $prediction['current_status']['health_score'] ?? null;

        return is_numeric($score) ? (int) round((float) $score) : null;
    }

    private function anomalyCount(Analytics $analysis): int
    {
        $anomalies = is_array($analysis->results) ? ($analysis->results['anomalies'] ?? null) : null;

        return is_array($anomalies) ? count($anomalies) : 0;
    }

    private function recommendations(Analytics $analysis): array
    {
        if (! is_array($analysis->recommendations)) {
            return [];
        }

        return collect($analysis->recommendations)
            ->filter(fn (mixed $item) => is_array($item) && is_string($item['action'] ?? null))
            ->map(fn (array $item) => [
                'priority' => is_string($item['priority'] ?? null) ? $item['priority'] : null,
                'action' => $item['action'],
                'description' => is_string($item['description'] ?? null) ? $item['description'] : null,
                'urgency_days' => is_numeric($item['urgency_days'] ?? null) ? (int) $item['urgency_days'] : null,
                'source' => is_string($item['source'] ?? null) ? $item['source'] : null,
            ])
            ->values()
            ->sortBy([
                fn (array $a, array $b) => $this->rank(item: $a) <=> $this->rank(item: $b),
                fn (array $a, array $b) => ($a['urgency_days'] ?? PHP_INT_MAX) <=> ($b['urgency_days'] ?? PHP_INT_MAX),
            ])
            ->values()
            ->all();
    }

    private function rank(array $item): int
    {
        return self::PRIORITY_RANK[$item['priority']] ?? self::MISSING_RANK;
    }
}
