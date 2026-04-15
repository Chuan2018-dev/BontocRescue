<?php

namespace App\Services;

use App\Models\IncidentReport;
use App\Support\AiSeverityMapper;
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
            && filled($report->evidence_path);
    }

    public function analyzeStoredEvidence(IncidentReport $report): array
    {
        if (! $this->shouldAnalyze($report)) {
            throw new RuntimeException('Report is not eligible for AI image severity analysis.');
        }

        if (! Storage::exists($report->evidence_path)) {
            throw new RuntimeException('Stored evidence file was not found for AI analysis.');
        }

        $response = Http::timeout((int) config('services.ai_severity.timeout', 20))
            ->attach(
                'file',
                Storage::get($report->evidence_path),
                $report->evidence_original_name ?: basename((string) $report->evidence_path)
            )
            ->post(rtrim((string) config('services.ai_severity.url', 'http://127.0.0.1:8100'), '/').'/predict');

        $response->throw();

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('AI service returned an invalid response payload.');
        }

        return AiSeverityMapper::fromAiServiceResponse(
            payload: $payload,
            modelName: $this->modelName(),
            modelVersion: $this->modelVersion(),
        );
    }
}
