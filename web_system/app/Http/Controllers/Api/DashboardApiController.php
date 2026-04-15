<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $reportQuery = IncidentReport::query()->with(['assignedResponder']);

        if ($user->isCivilian()) {
            $reportQuery->where('reported_by', $user->id);
        }

        $reports = $reportQuery->latest()->take(6)->get();
        $activeQuery = IncidentReport::query()->whereIn('status', ['received', 'acknowledged', 'dispatched', 'responding']);
        $scopeQuery = $user->isCivilian()
            ? $activeQuery->where('reported_by', $user->id)
            : IncidentReport::query()->whereIn('status', ['received', 'acknowledged', 'dispatched', 'responding']);

        $summary = [
            'active_alerts' => (clone $scopeQuery)->count(),
            'my_reports' => IncidentReport::query()->where('reported_by', $user->id)->count(),
            'transmitted_today' => IncidentReport::query()
                ->when($user->isCivilian(), fn ($query) => $query->where('reported_by', $user->id))
                ->whereDate('transmitted_at', today())
                ->count(),
            'assigned_to_me' => IncidentReport::query()
                ->where('assigned_responder_id', $user->id)
                ->whereIn('status', ['acknowledged', 'dispatched', 'responding'])
                ->count(),
            'fatal_reports' => IncidentReport::query()
                ->when($user->isCivilian(), fn ($query) => $query->where('reported_by', $user->id))
                ->where('severity', 'Fatal')
                ->count(),
            'online_reports' => IncidentReport::query()
                ->when($user->isCivilian(), fn ($query) => $query->where('reported_by', $user->id))
                ->where('transmission_type', 'online')
                ->count(),
            'lora_reports' => IncidentReport::query()
                ->when($user->isCivilian(), fn ($query) => $query->where('reported_by', $user->id))
                ->where('transmission_type', 'lora')
                ->count(),
        ];

        $notifications = $reports->take(3)->map(function (IncidentReport $report) use ($user): string {
            if ($user->isCivilian()) {
                return $report->report_code.' - '.$report->severity.' - '.ucfirst($report->status);
            }

            return $report->incident_type.' - '.$report->severity.' - '.$report->location_text;
        })->values();

        return response()->json([
            'application' => config('app.name'),
            'user' => $this->userPayload($user->fresh('responderProfile')),
            'summary' => $summary,
            'recent_reports' => $reports->map(fn (IncidentReport $report): array => $this->reportPayload($report))->values(),
            'map_points' => $reports
                ->filter(fn (IncidentReport $report): bool => $report->latitude !== null && $report->longitude !== null)
                ->map(fn (IncidentReport $report): array => [
                    'id' => $report->id,
                    'label' => $report->incident_type,
                    'severity' => $report->severity,
                    'latitude' => (float) $report->latitude,
                    'longitude' => (float) $report->longitude,
                ])
                ->values(),
            'notifications' => $notifications,
        ]);
    }

    private function userPayload(User $user): array
    {
        $profile = $user->responderProfile;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_admin' => (bool) $user->is_admin,
            'phone' => $profile?->phone,
            'station' => $profile?->assigned_station ?? ($user->isCivilian() ? 'Civilian Mobile' : 'Bontoc HQ'),
            'connectivity_mode' => $profile?->connectivity_mode ?? 'auto_select',
            'notification_profile' => $profile?->notification_profile ?? 'push_notifications',
        ];
    }

    private function reportPayload(IncidentReport $report): array
    {
        return [
            'id' => $report->id,
            'report_code' => $report->report_code,
            'reporter_name' => $report->reporter_name,
            'reporter_contact' => $report->reporter_contact,
            'incident_type' => $report->incident_type,
            'severity' => $report->severity,
            'status' => $report->status,
            'channel' => $report->channel,
            'transmission_type' => $report->transmission_type,
            'location_text' => $report->location_text,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'description' => $report->description,
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
            'evidence_original_name' => $report->evidence_original_name,
            'evidence_available' => $report->evidence_path !== null,
            'evidence_url' => $report->evidence_path ? route('api.reports.evidence', $report) : null,
            'selfie_original_name' => $report->reporter_selfie_original_name,
            'selfie_available' => $report->reporter_selfie_path !== null,
            'selfie_url' => $report->reporter_selfie_path ? route('api.reports.selfie', $report) : null,
            'assigned_responder_name' => $report->assignedResponder?->name,
            'response_notes' => $report->response_notes,
            'transmitted_at' => optional($report->transmitted_at)->toIso8601String(),
            'created_at' => optional($report->created_at)->toIso8601String(),
        ];
    }
}

