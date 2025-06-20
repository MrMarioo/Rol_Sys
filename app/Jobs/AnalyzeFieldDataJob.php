<?php

namespace App\Jobs;

use App\Models\Analytics;
use App\Models\FieldData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzeFieldDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public FieldData $fieldData
    ) {}

    public function handle(): void
    {
        try {
            $response = Http::timeout(300)
                ->post('http://ai-service:5000/analyze/field/' . $this->fieldData->field_id, [
                    'field_data' => [$this->fieldData->toArray()],
                    'parameters' => ['sensitivity' => 'medium']
                ]);

            if ($response->successful()) {
                $results = $response->json();

                Analytics::create([
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
            }
        } catch (\Exception $e) {
            Log::error('AI Analysis Job failed: ' . $e->getMessage());
            $this->fail($e);
        }
    }
}
