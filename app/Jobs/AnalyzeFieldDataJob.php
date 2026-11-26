<?php

namespace App\Jobs;

use App\Models\Analytics;
use App\Models\FieldData;
use App\Notifications\AnalysisCompleted;
use App\Notifications\AnalysisFailed;
use App\Services\AiAnalysisClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AnalyzeFieldDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 85;

    public function __construct(
        public FieldData $fieldData,
    ) {}

    public function handle(AiAnalysisClient $aiClient): void
    {
        $results = $aiClient->analyzeField(
            fieldId: $this->fieldData->field_id,
            payload: [
                'field_data' => [$this->fieldData->toArray()],
                'parameters' => ['sensitivity' => 'medium'],
            ],
        );

        /** @var Analytics $analytics */
        $analytics = Analytics::query()->create(attributes: [
            'field_id' => $this->fieldData->field_id,
            'analysis_type' => 'auto_analysis',
            'analysis_date' => now(),
            'results' => $results,
            'recommendations' => $results['recommendations'] ?? [],
            'parameters' => [
                'triggered_by' => 'new_field_data',
                'data_id' => $this->fieldData->id,
                'auto_generated' => true,
            ],
        ]);

        $this->fieldData->field->user->notify(new AnalysisCompleted(analytics: $analytics));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Automatic field data analysis failed', [
            'field_data_id' => $this->fieldData->id,
            'message' => $exception->getMessage(),
        ]);

        $field = $this->fieldData->field;
        $field->user->notify(new AnalysisFailed(field: $field));
    }
}
