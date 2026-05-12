<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeStatuses = ['received', 'acknowledged', 'dispatched', 'responding'];
        $recentReports = IncidentReport::query()
            ->with(['assignedResponder', 'reporter'])
            ->latest('status_updated_at')
            ->latest('created_at')
            ->take(12)
            ->get();

        $priorityIncident = IncidentReport::query()
            ->with(['assignedResponder'])
            ->whereIn('status', $activeStatuses)
            ->orderByRaw("case severity when 'Fatal' then 1 when 'Serious' then 2 when 'Minor' then 3 else 4 end")
            ->oldest('created_at')
            ->first();

        $responders = User::query()
            ->where('role', 'responder')
            ->with('responderProfile')
            ->withCount([
                'assignedReports as active_assignment_count' => fn ($query) => $query->whereIn('status', $activeStatuses),
                'assignedReports as resolved_assignment_count' => fn ($query) => $query->where('status', 'resolved'),
            ])
            ->orderByDesc('is_admin')
            ->orderByDesc('active_assignment_count')
            ->orderBy('name')
            ->take(8)
            ->get();

        $recentCivilians = User::query()
            ->where('role', 'civilian')
            ->with('responderProfile')
            ->latest()
            ->take(8)
            ->get();

        $moderationQueue = IncidentReport::query()
            ->with(['assignedResponder', 'reporter'])
            ->where(function ($query) use ($activeStatuses) {
                $query
                    ->where(function ($activeQuery) use ($activeStatuses) {
                        $activeQuery
                            ->whereIn('status', $activeStatuses)
                            ->whereNull('assigned_responder_id');
                    })
                    ->orWhere('status', 'rejected');
            })
            ->orderByRaw("case when status = 'rejected' then 1 when severity = 'Fatal' then 2 when severity = 'Serious' then 3 else 4 end")
            ->latest('status_updated_at')
            ->latest('created_at')
            ->take(8)
            ->get();

        return view('admin.dashboard', [
            'user' => Auth::user(),
            'stats' => [
                'civilians' => User::query()->where('role', 'civilian')->count(),
                'responders' => User::query()->where('role', 'responder')->count(),
                'admins' => User::query()->where('is_admin', true)->count(),
                'active_reports' => IncidentReport::query()->whereIn('status', $activeStatuses)->count(),
                'unassigned_reports' => IncidentReport::query()->whereIn('status', $activeStatuses)->whereNull('assigned_responder_id')->count(),
                'reports_today' => IncidentReport::query()->whereDate('created_at', now()->toDateString())->count(),
                'fatal_alerts' => IncidentReport::query()->where('severity', 'Fatal')->whereIn('status', $activeStatuses)->count(),
                'resolved_today' => IncidentReport::query()->whereDate('resolved_at', now()->toDateString())->where('status', 'resolved')->count(),
                'rejected_today' => IncidentReport::query()->whereDate('status_updated_at', now()->toDateString())->where('status', 'rejected')->count(),
            ],
            'severityBreakdown' => [
                'Minor' => IncidentReport::query()->where('severity', 'Minor')->count(),
                'Serious' => IncidentReport::query()->where('severity', 'Serious')->count(),
                'Fatal' => IncidentReport::query()->where('severity', 'Fatal')->count(),
            ],
            'statusBreakdown' => [
                'Unassigned' => IncidentReport::query()->whereIn('status', $activeStatuses)->whereNull('assigned_responder_id')->count(),
                'Active Dispatch' => IncidentReport::query()->whereIn('status', ['acknowledged', 'dispatched', 'responding'])->count(),
                'Resolved' => IncidentReport::query()->where('status', 'resolved')->count(),
                'Rejected' => IncidentReport::query()->where('status', 'rejected')->count(),
            ],
            'transmissionBreakdown' => [
                'online' => IncidentReport::query()->where('transmission_type', 'online')->count(),
                'lora' => IncidentReport::query()->where('transmission_type', 'lora')->count(),
            ],
            'recentReports' => $recentReports,
            'priorityIncident' => $priorityIncident,
            'dispatchRecommendation' => $priorityIncident ? $this->dispatchRecommendationFor($priorityIncident) : null,
            'fatalAlerts' => IncidentReport::query()
                ->where('severity', 'Fatal')
                ->whereIn('status', $activeStatuses)
                ->latest()
                ->take(5)
                ->get(),
            'connectivity' => $this->connectivityIntelligence(),
            'mapPoints' => $recentReports->filter(
                fn (IncidentReport $report): bool => $report->latitude !== null && $report->longitude !== null
            ),
            'responders' => $responders,
            'recentCivilians' => $recentCivilians,
            'moderationQueue' => $moderationQueue,
            'auditTrail' => $this->adminAuditTrail(),
        ]);
    }

    private function connectivityIntelligence(): array
    {
        $recentReports = IncidentReport::query()
            ->latest()
            ->take(12)
            ->get();

        $loraRecent = $recentReports->where('transmission_type', 'lora')->count();
        $onlineRecent = $recentReports->where('transmission_type', 'online')->count();

        return [
            'online_status' => $onlineRecent > 0 ? 'Online gateway stable' : 'Online standby',
            'lora_status' => $loraRecent > 0 ? 'LoRa fallback active' : 'LoRa standby',
            'internet_status' => $loraRecent > $onlineRecent ? 'Internet degraded - fallback preferred' : 'Internet uplink healthy',
            'gateway_status' => $loraRecent > 0 || $onlineRecent > 0 ? 'Gateway synchronized' : 'Awaiting field traffic',
            'websocket_status' => 'Reverb live sync online',
            'queue_status' => 'Immediate broadcast channel',
            'api_status' => 'Laravel API healthy',
        ];
    }

    private function dispatchRecommendationFor(IncidentReport $report): array
    {
        $priority = match ($report->severity) {
            'Fatal' => 'Priority 1',
            'Serious' => 'Priority 2',
            default => 'Priority 3',
        };

        $responseType = match ($report->severity) {
            'Fatal' => 'Immediate medical and extraction support',
            'Serious' => 'Rapid structural or trauma assessment',
            default => 'Verification and scene control',
        };

        return [
            'priority' => $priority,
            'response_type' => $responseType,
            'recommended_responder' => $report->assignedResponder?->name ?? 'Nearest available responder',
            'timer_label' => $this->priorityTimerLabel($report),
        ];
    }

    private function priorityTimerLabel(IncidentReport $report): string
    {
        if ($report->created_at === null) {
            return 'Just received';
        }

        $minutes = max(1, (int) ceil($report->created_at->diffInMinutes(now())));

        return $minutes.' min active';
    }

    private function adminAuditTrail(int $limit = 10): array
    {
        return IncidentReport::query()
            ->with(['assignedResponder', 'reporter'])
            ->latest('status_updated_at')
            ->latest('created_at')
            ->take(18)
            ->get()
            ->flatMap(function (IncidentReport $report) {
                $entries = collect($report->coordination_log ?? [])
                    ->filter(fn ($entry): bool => is_array($entry))
                    ->map(function (array $entry) use ($report) {
                        return [
                            'report_code' => $report->report_code,
                            'incident_type' => $report->incident_type,
                            'actor_name' => $entry['actor_name'] ?? 'System',
                            'actor_role' => $entry['actor_role'] ?? 'Responder',
                            'label' => $entry['label'] ?? 'Updated incident coordination',
                            'status' => $entry['status'] ?? $report->status,
                            'assigned_responder_name' => $entry['assigned_responder_name'] ?? $report->assignedResponder?->name,
                            'occurred_at' => $entry['occurred_at'] ?? optional($report->status_updated_at)->toIso8601String(),
                            'response_notes' => $entry['response_notes'] ?? $report->response_notes,
                        ];
                    });

                if ($entries->isNotEmpty()) {
                    return $entries;
                }

                return [[
                    'report_code' => $report->report_code,
                    'incident_type' => $report->incident_type,
                    'actor_name' => $report->reporter_name ?: ($report->reporter?->name ?? 'Reporter'),
                    'actor_role' => 'Reporter',
                    'label' => 'Submitted incident report',
                    'status' => $report->status,
                    'assigned_responder_name' => $report->assignedResponder?->name,
                    'occurred_at' => optional($report->created_at)->toIso8601String(),
                    'response_notes' => null,
                ]];
            })
            ->sortByDesc(fn (array $entry): string => (string) ($entry['occurred_at'] ?? ''))
            ->take($limit)
            ->values()
            ->all();
    }
}

