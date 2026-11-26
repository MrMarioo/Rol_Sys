<?php

namespace App\Services;

use App\Exceptions\AiServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AiAnalysisClient
{
    public function analyzeField(int $fieldId, array $payload): array
    {
        try {
            $response = $this->request()->post(
                url: sprintf('analyze/field/%d', $fieldId),
                data: $payload,
            );
        } catch (ConnectionException $e) {
            throw new AiServiceException(message: 'AI service is unreachable.', previous: $e);
        }

        if (! $response->successful()) {
            Log::warning('AI service request failed', [
                'field_id' => $fieldId,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 2000),
            ]);

            throw new AiServiceException(message: sprintf('AI service returned HTTP %d.', $response->status()));
        }

        $results = $response->json();

        if (! is_array($results)) {
            throw new AiServiceException(message: 'AI service returned an invalid response.');
        }

        return $results;
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl(url: rtrim(config('services.ai.url'), '/'))
            ->acceptJson()
            ->connectTimeout(seconds: (int) config('services.ai.connect_timeout'))
            ->timeout(seconds: (int) config('services.ai.timeout'));

        $token = config('services.ai.token');

        return filled($token) ? $request->withHeaders(headers: ['X-AI-Token' => $token]) : $request;
    }
}
