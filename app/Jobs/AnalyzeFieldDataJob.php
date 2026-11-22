<?php

namespace App\Jobs;

use App\Models\Analytics;
use App\Models\FieldData;
use App\Notifications\AnalysisCompleted;
use App\Notifications\AnalysisFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AnalyzeFieldDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const HTTP_TIMEOUT = 75;

    public int $timeout = 85;

    public function __construct(
        public FieldData $fieldData
    ) {}

    public function handle(): void
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->post('http://ai-service:5000/analyze/field/' . $this->fieldData->field_id, [
                'field_data' => [$this->fieldData->toArray()],
                'parameters' => ['sensitivity' => 'medium']
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf('AI service returned %d', $response->status()));
        }

        $results = $response->json();

        $analytics = Analytics::create([
            'field_id' => $this->fieldData->field_id,
            'analysis_type' => 'auto_analysis',
            'analysis_date' => now(),
            'results' => $results,
            'recommendations' => $results['recommendations'] ?? [],
            'parameters' => [
                'triggered_by' => 'new_field_data',
                'data_id' => $this->fieldData->id,
                'auto_generated' => true
            ]
        ]);

        $this->fieldData->field?->user?->notify(new AnalysisCompleted($analytics));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('AI Analysis Job failed: ' . $exception->getMessage());

        $field = $this->fieldData->field;
        $field?->user?->notify(new AnalysisFailed($field));
    }
}
