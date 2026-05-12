<?php

namespace App\Events;

use App\Models\IncidentReport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentFeedUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public IncidentReport $report,
        public string $action = 'created',
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('responders.incidents');
    }

    public function broadcastAs(): string
    {
        return 'incident.feed.updated';
    }

    public function broadcastWith(): array
    {
        $report = $this->report->loadMissing(['assignedResponder']);
        $priorityMinutes = $this->priorityMinutesFor($report);
        $recommendation = $this->recommendationFor($report);

        return [
            'action' => $this->action,
            'message' => $this->messageFor($report),
            'report' => [
                'id' => $report->id,
                'report_code' => $report->report_code,
                'incident_type' => $report->incident_type,
                'severity' => $report->severity,
                'status' => $report->status,
                'channel' => $report->channel,
                'transmission_type' => $report->transmission_type,
                'location_text' => $report->location_text,
                'latitude' => $report->latitude !== null ? (float) $report->latitude : null,
                'longitude' => $report->longitude !== null ? (float) $report->longitude : null,
                'description' => $report->description,
                'reporter_name' => $report->reporter_name,
                'assigned_responder_name' => $report->assignedResponder?->name,
                'ai_summary' => $report->ai_summary,
                'ai_confidence' => $report->ai_confidence,
                'ai_source' => $report->ai_source,
                'ai_status' => $report->ai_status,
                'ai_model_name' => $report->ai_model_name,
                'ai_model_version' => $report->ai_model_version,
                'ai_review_required' => (bool) $report->ai_review_required,
                'ai_probabilities' => $report->ai_probabilities,
                'ai_processed_at' => optional($report->ai_processed_at)->toIso8601String(),
                'ai_error_message' => $report->ai_error_message,
                'evidence_type' => $report->evidence_type,
                'evidence_available' => $report->evidence_path !== null,
                'selfie_available' => $report->reporter_selfie_path !== null,
                'response_notes' => $report->response_notes,
                'priority_minutes' => $priorityMinutes,
                'priority_timer_label' => $priorityMinutes.' min active',
                'dispatch_recommendation' => $recommendation,
                'created_at' => optional($report->created_at)->toIso8601String(),
                'updated_at' => optional($report->updated_at)->toIso8601String(),
                'detail_url' => route('reports.show', $report),
            ],
        ];
    }

    private function messageFor(IncidentReport $report): string
    {
        if ($this->action === 'ai_updated') {
            return 'AI severity updated for '.$report->report_code.' to '.$report->severity.'.';
        }

        if ($this->action === 'updated') {
            return $report->report_code.' status changed to '.ucfirst($report->status).'.';
        }

        return 'New '.$report->severity.' incident at '.$report->location_text.'.';
    }

    private function priorityMinutesFor(IncidentReport $report): int
    {
        if ($report->created_at === null) {
            return 0;
        }

        return max(1, (int) ceil($report->created_at->diffInMinutes(now())));
    }

    private function recommendationFor(IncidentReport $report): array
    {
        $priority = match ($report->severity) {
            'Fatal' => 'Priority 1',
            'Serious' => 'Priority 2',
            default => 'Priority 3',
        };

        $responseType = match ($report->severity) {
            'Fatal' => 'Medical and extraction',
            'Serious' => 'Rapid field assessment',
            default => 'Verification patrol',
        };

        $recommendedResponder = $report->assignedResponder?->name ?? 'Nearest available responder';

        return [
            'priority' => $priority,
            'response_type' => $responseType,
            'recommended_responder' => $recommendedResponder,
        ];
    }
}

