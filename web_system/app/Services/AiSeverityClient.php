<?php

namespace App\Services;

use App\Models\IncidentReport;
use App\Support\AiSeverityMapper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AiSeverityClient
{
    public function enabled(): bool
    {
        return (bool) config('services.ai_severity.enabled', true);
    }

    public function dispatchMode(): string
    {
        return (string) config('services.ai_severity.dispatch', 'sync');
    }

    public function modelName(): string
    {
        return (string) config('services.ai_severity.model_name', 'bontoc_southern_leyte_severity_baseline');
    }

    public function modelVersion(): string
    {
        return (string) config('services.ai_severity.model_version', '0.1.0');
    }

    public function shouldAnalyze(IncidentReport $report): bool
    {
        return $this->enabled()
            && $report->transmission_type === 'online'
            && $report->evidence_type === 'photo'
            && filled($report->evidence_path)
            && (($report->ai_status ?? null) === 'pending' || $report->ai_processed_at === null);
    }

    public function analyzeStoredEvidence(IncidentReport $report): array
    {
        if (! $this->shouldAnalyze($report)) {
            throw new RuntimeException('Report is not eligible for AI image severity analysis.');
        }

        if (! Storage::exists($report->evidence_path)) {
            throw new RuntimeException('Stored evidence file was not found for AI analysis.');
        }

        $payload = $this->requestPrediction(
            fileBytes: Storage::get($report->evidence_path),
            filename: $report->evidence_original_name ?: basename((string) $report->evidence_path),
        );

        if (($payload['accepted'] ?? true) !== true) {
            throw new RuntimeException((string) ($payload['rejection_message'] ?? 'AI relevance gate rejected the uploaded evidence photo.'));
        }

        return AiSeverityMapper::fromAiServiceResponse(
            payload: $payload,
            modelName: $this->modelName(),
            modelVersion: $this->modelVersion(),
        );
    }

    public function analyzeUploadedEvidence(UploadedFile $file): array
    {
        $payload = $this->requestPrediction(
            fileBytes: $file->get(),
            filename: $file->getClientOriginalName(),
        );

        $accepted = (bool) ($payload['accepted'] ?? true);

        return [
            'accepted' => $accepted,
            'rejection_message' => $accepted
                ? null
                : (string) ($payload['rejection_message'] ?? 'Upload a real accident or emergency scene photo.'),
            'photo_relevance_label' => $payload['photo_relevance_label'] ?? null,
            'photo_relevance_confidence' => $payload['photo_relevance_confidence'] ?? null,
            'analysis' => $accepted
                ? AiSeverityMapper::fromAiServiceResponse(
                    payload: $payload,
                    modelName: $this->modelName(),
                    modelVersion: $this->modelVersion(),
                )
                : null,
        ];
    }

    private function requestPrediction(string $fileBytes, string $filename): array
    {
        $attempts = max(1, (int) config('services.ai_severity.retry_attempts', 1));
        $retryDelayMs = max(0, (int) config('services.ai_severity.retry_delay_ms', 1500));
        $endpoint = rtrim((string) config('services.ai_severity.url', 'http://127.0.0.1:8100'), '/').'/predict';
        $lastException = null;
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::timeout((int) config('services.ai_severity.timeout', 20))
                    ->attach('file', $fileBytes, $filename)
                    ->post($endpoint);

                if ($response->successful() || ! $this->shouldRetryResponse($response->status(), $attempt, $attempts)) {
                    break;
                }
            } catch (\Throwable $exception) {
                $lastException = $exception;

                if ($attempt >= $attempts) {
                    throw $exception;
                }
            }

            if ($attempt < $attempts && $retryDelayMs > 0) {
                usleep($retryDelayMs * 1000);
            }
        }

        if ($response === null) {
            throw $lastException ?? new RuntimeException('AI service did not return a response.');
        }

        $response->throw();

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('AI service returned an invalid response payload.');
        }

        return $payload;
    }

    private function shouldRetryResponse(int $status, int $attempt, int $attempts): bool
    {
        if ($attempt >= $attempts) {
            return false;
        }

        return $status === 408
            || $status === 425
            || $status === 429
            || $status >= 500;
    }
}
