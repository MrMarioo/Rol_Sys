<?php

namespace Tests\Unit;

use Database\Factories\Support\AnalysisResultsBuilder;
use PHPUnit\Framework\TestCase;

class AnalysisResultsBuilderTest extends TestCase
{
    public function test_statuses_follow_ai_thresholds(): void
    {
        $ndviCases = [[0.29, 'Critical'], [0.49, 'Poor'], [0.69, 'Moderate'], [0.7, 'Good']];

        foreach ($ndviCases as [$avg, $status]) {
            $results = AnalysisResultsBuilder::build(
                ndvi: $this->stats($avg),
                moisture: $this->stats(0.5),
                date: '2026-11-20',
                withPrediction: false,
            );

            $this->assertSame($status, $results['vegetation_health']['health_status']);
        }

        $moistureCases = [[0.29, 'Low'], [0.71, 'High'], [0.5, 'Optimal']];

        foreach ($moistureCases as [$avg, $status]) {
            $results = AnalysisResultsBuilder::build(
                ndvi: $this->stats(0.75),
                moisture: $this->stats($avg),
                date: '2026-11-20',
                withPrediction: false,
            );

            $this->assertSame($status, $results['soil_moisture']['moisture_status']);
        }
    }

    public function test_low_moisture_and_poor_ndvi_produce_high_priority_recommendations(): void
    {
        $results = AnalysisResultsBuilder::build(
            ndvi: $this->stats(0.45),
            moisture: $this->stats(0.25),
            date: '2026-11-20',
            withPrediction: false,
        );

        $byAction = collect($results['recommendations'])->keyBy('action');

        $this->assertTrue($byAction->has('Inspect crop condition'));
        $this->assertTrue($byAction->has('Increase irrigation'));
        $this->assertSame('high', $byAction['Inspect crop condition']['priority']);
        $this->assertSame('high', $byAction['Increase irrigation']['priority']);
        $this->assertSame('Low NDVI (0.45) indicates potential crop health issues.', $byAction['Inspect crop condition']['description']);
    }

    public function test_healthy_field_gets_default_recommendation(): void
    {
        $results = AnalysisResultsBuilder::build(
            ndvi: ['avg' => 0.72, 'min' => 0.55, 'max' => 0.85],
            moisture: ['avg' => 0.45, 'min' => 0.30, 'max' => 0.60],
            date: '2026-11-20',
            withPrediction: false,
        );

        $this->assertCount(1, $results['recommendations']);
        $this->assertSame('Continue standard procedures', $results['recommendations'][0]['action']);
        $this->assertSame([], $results['anomalies']);
        $this->assertArrayNotHasKey('growth_prediction', $results);
    }

    public function test_prediction_contains_keys_read_by_show_page(): void
    {
        $results = AnalysisResultsBuilder::build(
            ndvi: $this->stats(0.65),
            moisture: $this->stats(0.4),
            date: '2026-11-20',
            withPrediction: true,
        );

        $prediction = $results['growth_prediction'];

        $this->assertArrayHasKey('current_biomass', $prediction);
        $this->assertArrayHasKey('health_score', $prediction['current_status']);
        $this->assertArrayHasKey('days_since_planting', $prediction['current_status']);
        $this->assertArrayHasKey('avg', $prediction['summary']['confidence_range']);
        $this->assertCount(7, $prediction['predictions']);
        $this->assertArrayHasKey('model_type', $prediction['model_info']);
        $this->assertArrayHasKey('growth_rate', $prediction['predictions'][0]);
        $this->assertArrayHasKey('growth_stage', $prediction['predictions'][0]);
    }

    public function test_missing_ndvi_gives_null_vegetation_health(): void
    {
        $results = AnalysisResultsBuilder::build(
            ndvi: null,
            moisture: $this->stats(0.5),
            date: '2026-11-20',
            withPrediction: false,
        );

        $this->assertNull($results['vegetation_health']);
        $this->assertSame('Optimal', $results['soil_moisture']['moisture_status']);
    }

    private function stats(float $avg): array
    {
        return ['avg' => $avg, 'min' => round($avg - 0.05, 2), 'max' => round($avg + 0.05, 2)];
    }
}
