<?php

namespace App\Services;

use App\Models\Field;
use App\Models\FieldData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class FieldDataSummaryService
{
    private const RECENT_LIMIT = 5;

    private const SERIES_DAYS = 90;

    private const EMPTY_DISPLAY = '—';

    private const METRICS = [
        'ndvi' => ['key' => 'avg_ndvi', 'label' => 'NDVI', 'unit' => null, 'kind' => 'numeric', 'decimals' => 3, 'scale' => 1],
        'soil_moisture' => ['key' => 'avg_moisture', 'label' => 'Soil moisture', 'unit' => '%', 'kind' => 'numeric', 'decimals' => 1, 'scale' => 100],
        'weather' => ['key' => 'temperature_avg', 'label' => 'Air temperature', 'unit' => '°C', 'kind' => 'numeric', 'decimals' => 1, 'scale' => 1],
        'soil_temperature' => ['key' => 'avg_temperature', 'label' => 'Soil temperature', 'unit' => '°C', 'kind' => 'numeric', 'decimals' => 1, 'scale' => 1],
        'biomass_measurement' => ['key' => 'dry_biomass_kg_per_ha', 'label' => 'Dry biomass', 'unit' => 'kg/ha', 'kind' => 'numeric', 'decimals' => 0, 'scale' => 1],
        'sunlight' => ['key' => 'sunshine_hours', 'label' => 'Sunshine', 'unit' => 'h', 'kind' => 'numeric', 'decimals' => 1, 'scale' => 1],
        'growth_stage' => ['key' => 'growth_stage', 'label' => 'Growth stage', 'unit' => null, 'kind' => 'text'],
        'fertilizer_application' => ['key' => 'fertilizer_type', 'label' => 'Last fertilization', 'unit' => null, 'kind' => 'text'],
    ];

    public function summarize(Field $field): array
    {
        $records = $field->fieldData()
            ->select(['id', 'field_id', 'data_type', 'collection_date', 'data'])
            ->orderByDesc('collection_date')
            ->orderByDesc('id')
            ->get();

        $readings = $records->map(fn (FieldData $record) => $this->toReading(record: $record));

        return [
            'latestReadings' => $this->latestReadings(readings: $readings),
            'trendSeries' => $this->trendSeries(readings: $readings),
            'recentReadings' => $this->recentReadings(readings: $readings),
        ];
    }

    private function toReading(FieldData $record): array
    {
        $metric = self::METRICS[$record->data_type] ?? null;
        $data = is_array($record->data) ? $record->data : [];
        $value = $metric ? $this->extractValue(metric: $metric, data: $data) : null;

        return [
            'id' => $record->id,
            'type' => $record->data_type,
            'date' => $record->collection_date->toDateString(),
            'label' => $metric['label'] ?? Str::headline($record->data_type),
            'metric' => $metric,
            'value' => $value,
            'display' => $value === null ? self::EMPTY_DISPLAY : $this->formatDisplay(metric: $metric, data: $data, value: $value),
        ];
    }

    private function extractValue(array $metric, array $data): float|string|null
    {
        $raw = $data[$metric['key']] ?? null;

        if ($metric['kind'] === 'numeric') {
            return is_numeric($raw) && is_finite((float) $raw)
                ? round((float) $raw * $metric['scale'], $metric['decimals'])
                : null;
        }

        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    private function formatDisplay(array $metric, array $data, float|string $value): string
    {
        if ($metric['kind'] === 'numeric') {
            return number_format($value, $metric['decimals'], '.', '');
        }

        return match ($metric['key']) {
            'growth_stage' => is_scalar($data['bbch_code'] ?? null)
                ? sprintf('%s (BBCH %s)', $value, $data['bbch_code'])
                : $value,
            'fertilizer_type' => is_numeric($data['amount_per_hectare'] ?? null)
                ? sprintf('%s %s kg/ha', $value, $data['amount_per_hectare'])
                : $value,
            default => $value,
        };
    }

    private function latestReadings(Collection $readings): array
    {
        $validByType = $readings
            ->filter(fn (array $reading) => $reading['value'] !== null)
            ->groupBy('type');

        return collect(self::METRICS)
            ->keys()
            ->filter(fn (string $type) => $validByType->has($type))
            ->map(function (string $type) use ($validByType) {
                $typeReadings = $validByType->get($type)->values();
                $latest = $typeReadings->first();
                $previous = $typeReadings->get(1);

                return [
                    'type' => $type,
                    'label' => $latest['label'],
                    'unit' => $latest['metric']['unit'],
                    'kind' => $latest['metric']['kind'],
                    'display' => $latest['display'],
                    'date' => $latest['date'],
                    'trend' => $this->trendFor(latest: $latest, previous: $previous),
                ];
            })
            ->values()
            ->all();
    }

    private function trendFor(array $latest, ?array $previous): ?string
    {
        if ($previous === null || $latest['metric']['kind'] !== 'numeric') {
            return null;
        }

        return match ($latest['value'] <=> $previous['value']) {
            1 => 'up',
            -1 => 'down',
            default => 'flat',
        };
    }

    private function trendSeries(Collection $readings): array
    {
        return $readings
            ->filter(fn (array $reading) => $reading['value'] !== null && $reading['metric']['kind'] === 'numeric')
            ->groupBy('type')
            ->map(fn (Collection $typeReadings) => $this->seriesFor(typeReadings: $typeReadings))
            ->all();
    }

    private function seriesFor(Collection $typeReadings): array
    {
        $metric = $typeReadings->first()['metric'];
        $windowStart = Carbon::parse($typeReadings->first()['date'])
            ->subDays(self::SERIES_DAYS)
            ->toDateString();

        $points = $typeReadings
            ->filter(fn (array $reading) => $reading['date'] >= $windowStart)
            ->groupBy('date')
            ->map(fn (Collection $sameDay, string $date) => [
                'date' => $date,
                'value' => round((float) $sameDay->avg('value'), $metric['decimals']),
            ])
            ->sortKeys()
            ->values()
            ->all();

        return [
            'label' => $metric['label'],
            'unit' => $metric['unit'],
            'points' => $points,
        ];
    }

    private function recentReadings(Collection $readings): array
    {
        return $readings
            ->take(self::RECENT_LIMIT)
            ->map(fn (array $reading) => [
                'id' => $reading['id'],
                'date' => $reading['date'],
                'label' => $reading['label'],
                'display' => $reading['display'],
            ])
            ->values()
            ->all();
    }
}
