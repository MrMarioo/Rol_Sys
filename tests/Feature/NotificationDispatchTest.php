<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeFieldDataJob;
use App\Models\Field;
use App\Models\FieldData;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AnalysisCompleted;
use App\Notifications\AnalysisFailed;
use App\Notifications\ReportGenerated;
use App\Services\FieldAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class NotificationDispatchTest extends TestCase
{
    use RefreshDatabase;

    private const PARAMETERS = [
        'start_date' => null,
        'end_date' => null,
        'sensitivity' => 'medium',
        'include_growth_prediction' => false,
        'prediction_days' => 7,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_successful_analysis_notifies_owner(): void
    {
        Http::fake(['*' => Http::response(['recommendations' => []], 200)]);
        $field = Field::factory()->create();

        app(FieldAnalysisService::class)->analyze($field, self::PARAMETERS);

        Notification::assertSentTo($field->user, AnalysisCompleted::class);
        Notification::assertNotSentTo($field->user, AnalysisFailed::class);
    }

    public function test_failed_analysis_notifies_owner_and_rethrows(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);
        $field = Field::factory()->create();

        try {
            app(FieldAnalysisService::class)->analyze($field, self::PARAMETERS);
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException) {
        }

        Notification::assertSentTo($field->user, AnalysisFailed::class);
        Notification::assertNotSentTo($field->user, AnalysisCompleted::class);
    }

    public function test_job_success_notifies_owner(): void
    {
        Http::fake(['*' => Http::response(['recommendations' => []], 200)]);
        $fieldData = $this->fieldData();

        (new AnalyzeFieldDataJob($fieldData))->handle();

        Notification::assertSentTo($fieldData->field->user, AnalysisCompleted::class);
    }

    public function test_job_failure_notifies_owner_once(): void
    {
        Http::fake(fn () => throw new ConnectionException('AI down'));
        $fieldData = $this->fieldData();

        try {
            AnalyzeFieldDataJob::dispatch($fieldData);
        } catch (ConnectionException) {
        }

        Notification::assertSentToTimes($fieldData->field->user, AnalysisFailed::class, 1);
    }

    public function test_job_killed_by_worker_notifies_owner(): void
    {
        $fieldData = $this->fieldData();

        (new AnalyzeFieldDataJob($fieldData))->failed(new TimeoutExceededException('timed out'));

        Notification::assertSentTo($fieldData->field->user, AnalysisFailed::class);
    }

    public function test_job_timeout_fits_within_queue_retry_window(): void
    {
        $job = new AnalyzeFieldDataJob($this->fieldData());

        $this->assertGreaterThan(AnalyzeFieldDataJob::HTTP_TIMEOUT, $job->timeout);
        $this->assertLessThan(config('queue.connections.redis.retry_after'), $job->timeout);
        $this->assertLessThan(config('queue.connections.database.retry_after'), $job->timeout);
    }

    public function test_report_generation_notifies_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $report = Report::factory()->for($owner)->create();

        $this->actingAs($owner)->post(route('reports.generate', $report));

        Notification::assertSentTo($owner, ReportGenerated::class);
        Notification::assertNotSentTo($other, ReportGenerated::class);
    }

    private function fieldData(): FieldData
    {
        return FieldData::create([
            'field_id' => Field::factory()->create()->id,
            'collection_date' => now()->toDateString(),
            'data_type' => 'ndvi',
            'data' => ['values' => [0.5]],
        ]);
    }
}
