<?php

namespace Database\Factories\Support;

use Carbon\CarbonImmutable;

final class AnalysisResultsBuilder
{
    private const ANOMALY_THRESHOLD = 0.25;

    private const DEFAULT_NDVI = 0.5;

    private const DEFAULT_MOISTURE = 0.45;

    private const DAYS_SINCE_PLANTING = 90;

    private const AVERAGE_TEMPERATURE = 18.5;

    public static function build(?array $ndvi, ?array $moisture, string $date, bool $withPrediction, int $predictionDays = 7): array
    {
        $vegetation = self::vegetationHealth(ndvi: $ndvi);
        $soil = self::soilMoisture(moisture: $moisture);
        $anomalies = self::anomalies(vegetation: $vegetation, date: $date);
        $recommendations = self::basicRecommendations(vegetation: $vegetation, soil: $soil, anomalies: $anomalies);

        $results = [
            'analysis_date' => $date,
            'vegetation_health' => $vegetation,
            'soil_moisture' => $soil,
            'anomalies' => $anomalies,
            'recommendations' => $recommendations,
        ];

        if (! $withPrediction) {
            return $results;
        }

        $prediction = self::growthPrediction(ndvi: $ndvi, moisture: $moisture, date: $date, days: $predictionDays);

        $results['growth_prediction'] = $prediction;
        $results['recommendations'] = [
            ...$recommendations,
            ...self::predictionRecommendations(soil: $soil, prediction: $prediction),
        ];

        return $results;
    }

    private static function vegetationHealth(?array $ndvi): ?array
    {
        if ($ndvi === null) {
            return null;
        }

        [$status, $recommendation] = match (true) {
            $ndvi['avg'] < 0.3 => ['Critical', 'Immediate intervention recommended.'],
            $ndvi['avg'] < 0.5 => ['Poor', 'Additional fertilization recommended.'],
            $ndvi['avg'] < 0.7 => ['Moderate', 'Monitor crop condition.'],
            default => ['Good', 'Continue standard procedures.'],
        };

        return [
            'avg_ndvi' => (float) $ndvi['avg'],
            'min_ndvi' => (float) $ndvi['min'],
            'max_ndvi' => (float) $ndvi['max'],
            'health_status' => $status,
            'problem_area_percent' => $ndvi['avg'] >= 0.6 ? 0.0 : min(100.0, round((0.6 - $ndvi['avg']) * 150, 1)),
            'recommendation' => $recommendation,
        ];
    }

    private static function soilMoisture(?array $moisture): ?array
    {
        if ($moisture === null) {
            return null;
        }

        $status = self::moistureStatus(avg: $moisture['avg']);

        return [
            'avg_moisture' => (float) $moisture['avg'],
            'min_moisture' => (float) $moisture['min'],
            'max_moisture' => (float) $moisture['max'],
            'predicted_moisture' => (float) $moisture['avg'],
            'moisture_status' => $status,
            'dry_area_percent' => $moisture['avg'] >= 0.35 ? 0.0 : min(100.0, round((0.35 - $moisture['avg']) * 200, 1)),
            'recommendation' => match ($status) {
                'Low' => 'Increase irrigation',
                'High' => 'Reduce irrigation',
                default => 'Maintain irrigation',
            },
        ];
    }

    private static function moistureStatus(float $avg): string
    {
        return match (true) {
            $avg < 0.3 => 'Low',
            $avg > 0.7 => 'High',
            default => 'Optimal',
        };
    }

    private static function anomalies(?array $vegetation, string $date): array
    {
        if ($vegetation === null || $vegetation['avg_ndvi'] - $vegetation['min_ndvi'] <= self::ANOMALY_THRESHOLD) {
            return [];
        }

        return [[
            'date' => $date,
            'type' => 'ndvi_anomaly',
            'description' => sprintf(
                'Minimum NDVI (%.2f) is far below the field average (%.2f).',
                $vegetation['min_ndvi'],
                $vegetation['avg_ndvi'],
            ),
            'value' => $vegetation['min_ndvi'],
            'expected' => $vegetation['avg_ndvi'],
            'severity' => 'high',
        ]];
    }

    private static function basicRecommendations(?array $vegetation, ?array $soil, array $anomalies): array
    {
        $recommendations = [];

        if ($vegetation !== null && in_array($vegetation['health_status'], ['Critical', 'Poor'], true)) {
            $recommendations[] = [
                'type' => 'urgent',
                'priority' => 'high',
                'action' => 'Inspect crop condition',
                'description' => sprintf('Low NDVI (%.2f) indicates potential crop health issues.', $vegetation['avg_ndvi']),
                'source' => 'vegetation_analysis',
            ];
        }

        if ($soil !== null && $soil['moisture_status'] === 'Low') {
            $recommendations[] = [
                'type' => 'high',
                'priority' => 'high',
                'action' => 'Increase irrigation',
                'description' => sprintf('Soil moisture is below optimal levels (%.2f).', $soil['avg_moisture']),
                'source' => 'moisture_analysis',
            ];
        }

        if ($soil !== null && $soil['moisture_status'] === 'High') {
            $recommendations[] = [
                'type' => 'medium',
                'priority' => 'medium',
                'action' => 'Reduce irrigation',
                'description' => sprintf('Soil moisture is above optimal levels (%.2f).', $soil['avg_moisture']),
                'source' => 'moisture_analysis',
            ];
        }

        foreach ($anomalies as $anomaly) {
            if ($anomaly['severity'] === 'high') {
                $recommendations[] = [
                    'type' => 'urgent',
                    'priority' => 'high',
                    'action' => 'Investigate anomaly',
                    'description' => $anomaly['description'],
                    'source' => 'anomaly_detection',
                ];
            }
        }

        if ($recommendations === []) {
            $recommendations[] = [
                'type' => 'info',
                'priority' => 'low',
                'action' => 'Continue standard procedures',
                'description' => 'All parameters are within normal ranges.',
                'source' => 'general',
            ];
        }

        return $recommendations;
    }

    private static function predictionRecommendations(?array $soil, array $prediction): array
    {
        $recommendations = [[
            'type' => 'scheduled',
            'priority' => 'medium',
            'category' => 'monitoring',
            'action' => 'Plan next field inspection',
            'description' => sprintf(
                'Verify the predicted biomass gain of %.1f kg/ha during the next inspection.',
                $prediction['summary']['total_predicted_growth'],
            ),
            'urgency_days' => 7,
            'source' => 'growth_prediction',
        ]];

        if ($soil !== null && $soil['moisture_status'] === 'Low') {
            $recommendations[] = [
                'type' => 'irrigation',
                'priority' => 'high',
                'category' => 'irrigation',
                'action' => 'Irrigate within 3 days',
                'description' => 'Predicted soil moisture deficit will limit growth in the coming days.',
                'urgency_days' => 3,
                'source' => 'growth_prediction',
            ];
        }

        return $recommendations;
    }

    private static function growthPrediction(?array $ndvi, ?array $moisture, string $date, int $days): array
    {
        $avgNdvi = (float) ($ndvi['avg'] ?? self::DEFAULT_NDVI);
        $avgMoisture = (float) ($moisture['avg'] ?? self::DEFAULT_MOISTURE);
        $moistureScore = self::moistureStatus(avg: $avgMoisture) === 'Optimal' ? 1.0 : 0.5;
        $currentBiomass = round($avgNdvi * 7500, 1);
        $dailyGrowth = round($currentBiomass * 0.012 * $moistureScore, 1);
        $stage = self::growthStage(avgNdvi: $avgNdvi);
        $start = CarbonImmutable::parse($date);

        $predictions = array_map(
            fn (int $day): array => [
                'day' => $day,
                'date' => $start->addDays($day)->toDateString(),
                'predicted_biomass' => round($currentBiomass + $dailyGrowth * $day, 1),
                'daily_growth' => $dailyGrowth,
                'growth_rate' => $dailyGrowth,
                'confidence' => round(max(0.5, 0.95 - 0.03 * $day), 2),
                'growth_stage' => $stage,
                'estimated_ndvi' => round($avgNdvi, 3),
                'estimated_moisture' => round($avgMoisture, 3),
            ],
            range(1, $days),
        );

        $confidences = array_column($predictions, 'confidence');

        return [
            'current_biomass' => $currentBiomass,
            'current_status' => [
                'growth_stage' => $stage,
                'health_score' => (int) round(min(1, $avgNdvi / 0.8) * 70 + $moistureScore * 30),
                'days_since_planting' => self::DAYS_SINCE_PLANTING,
                'issues' => [],
                'warnings' => [],
                'key_metrics' => [
                    'ndvi' => round($avgNdvi, 3),
                    'soil_moisture' => round($avgMoisture, 3),
                    'temperature' => self::AVERAGE_TEMPERATURE,
                ],
            ],
            'predictions' => $predictions,
            'summary' => [
                'total_predicted_growth' => round($dailyGrowth * $days, 1),
                'average_daily_growth' => $dailyGrowth,
                'confidence_range' => [
                    'min' => min($confidences),
                    'max' => max($confidences),
                    'avg' => round(array_sum($confidences) / count($confidences), 2),
                ],
            ],
            'model_info' => [
                'model_type' => 'RandomForestRegressor',
                'features_used' => 12,
                'version' => '1.0.0',
                'last_updated' => $date,
            ],
        ];
    }

    private static function growthStage(float $avgNdvi): string
    {
        return match (true) {
            $avgNdvi < 0.3 => 'Early Vegetative',
            $avgNdvi < 0.6 => 'Vegetative Growth',
            default => 'Flowering',
        };
    }
}
