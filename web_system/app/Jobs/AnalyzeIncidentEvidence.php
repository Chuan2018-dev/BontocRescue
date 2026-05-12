<?php

namespace App\Jobs;

use App\Models\IncidentReport;
use App\Services\AiSeverityClient;
use App\Support\IncidentFeedBroadcaster;
use App\Support\AiSeverityMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AnalyzeIncidentEvidence implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public int $reportId)
    {
        $this->onQueue('ai-severity');
    }

    public function handle(AiSeverityClient $client): void
    {
        $report = IncidentReport::query()->find($this->reportId);

        if (! $report || ! $client->shouldAnalyze($report)) {
            return;
        }

        try {
            $analysis = $client->analyzeStoredEvidence($report);

            $report->update([
                'severity' => $analysis['severity'],
                'ai_summary' => $analysis['summary'],
                'ai_confidence' => $analysis['confidence'],
                'ai_source' => $analysis['source'],
                'ai_status' => $analysis['status'],
                'ai_model_name' => $analysis['model_name'],
                'ai_model_version' => $analysis['model_version'],
                'ai_review_required' => $analysis['review_required'],
                'ai_probabilities' => $analysis['probabilities'],
                'ai_processed_at' => now(),
                'ai_error_message' => null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('AI image analysis failed for incident report.', [
                'report_id' => $report->id,
                'report_code' => $report->report_code,
                'error' => $exception->getMessage(),
            ]);

            $fallback = AiSeverityMapper::fallbackFromDescription($report->description, $report->severity);

            $report->update([
                'severity' => $fallback['severity'],
                'ai_summary' => $fallback['summary'],
                'ai_confidence' => $fallback['confidence'],
                'ai_source' => 'description_fallback',
                'ai_status' => 'fallback',
                'ai_model_name' => $fallback['model_name'],
                'ai_model_version' => $fallback['model_version'],
                'ai_review_required' => false,
                'ai_probabilities' => $fallback['probabilities'],
                'ai_processed_at' => now(),
                'ai_error_message' => AiSeverityMapper::humanizeErrorMessage(
                    Str::limit($exception->getMessage(), 240)
                ),
            ]);
        }

        IncidentFeedBroadcaster::dispatch($report, 'ai_updated');
    }
}
