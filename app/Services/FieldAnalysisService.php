<?php

namespace App\Services;

use App\Models\Analytics;
use App\Models\Field;
use App\Models\FieldData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FieldAnalysisService
{
    public function __construct(
        private readonly Analytics $analytics,
    ) {}

    public function analyze(Field $field, array $parameters): Analytics
    {
        $payload = $this->buildPayload($field, $parameters);

        $response = Http::timeout(60)->post(
            sprintf('%s/analyze/field/%d', $this->aiServiceUrl(), $field->id),
            $payload,
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                sprintf('AI service returned %d: %s', $response->status(), $response->body()),
            );
        }

        $results = $response->json();

        /** @var Analytics $analytics */
        $analytics = $this->analytics->create([
            'field_id' => $field->id,
            'analysis_type' => $parameters['include_growth_prediction']
                ? 'comprehensive_with_prediction'
                : 'standard_analysis',
            'analysis_date' => now(),
            'results' => $results,
            'recommendations' => $results['recommendations'] ?? null,
            'parameters' => [
                'sensitivity' => $parameters['sensitivity'],
                'data_count' => count($payload['field_data']),
                'include_prediction' => $parameters['include_growth_prediction'],
                'prediction_days' => $parameters['prediction_days'],
                'date_range' => [
                    'from' => $parameters['start_date'],
                    'to' => $parameters['end_date'],
                ],
            ],
        ]);

        return $analytics;
    }

    private function buildPayload(Field $field, array $parameters): array
    {
        $fieldData = $this->collectFieldData($field, $parameters['start_date'], $parameters['end_date']);
        $activeCrop = $field->crops()
            ->wherePivot('status', 'active')
            ->whereNull('actual_harvest_date')
            ->first();

        return [
            'field_data' => $fieldData,
            'field_info' => [
                'size' => $field->size,
                'planting_date' => $activeCrop?->pivot->planting_date,
                'crop_type' => $activeCrop?->name,
            ],
            'parameters' => [
                'sensitivity' => $parameters['sensitivity'],
                'include_growth_prediction' => $parameters['include_growth_prediction'],
                'prediction_days' => $parameters['prediction_days'],
            ],
        ];
    }

    private function collectFieldData(Field $field, ?string $startDate, ?string $endDate): array
    {
        $query = FieldData::query()->where('field_id', $field->id);

        if ($startDate !== null) {
            $query->where('collection_date', '>=', $startDate);
        }
        if ($endDate !== null) {
            $query->where('collection_date', '<=', $endDate);
        }

        return $query->orderBy('collection_date', 'desc')
            ->get()
            ->map(fn (FieldData $item) => [
                'id' => $item->id,
                'field_id' => $item->field_id,
                'collection_date' => $item->collection_date?->format('Y-m-d'),
                'data_type' => $item->data_type,
                'data' => $item->data,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'metadata' => $item->metadata,
            ])
            ->toArray();
    }

    private function aiServiceUrl(): string
    {
        return config('services.ai.url', 'http://ai-service:5000');
    }
}