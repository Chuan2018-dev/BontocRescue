<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = Auth::user();
        $activeStatuses = ['received', 'acknowledged', 'dispatched', 'responding'];

        if ($user?->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        if ($user?->isCivilian()) {
            $reports = $this->visibleReportsQuery($user)
                ->latest('created_at')
                ->take(8)
                ->get();

            return view('dashboard', [
                'dashboardMode' => 'civilian',
                'user' => $user,
                'stats' => [
                    'total' => $this->visibleReportsQuery($user)->count(),
                    'active' => $this->visibleReportsQuery($user)->whereIn('status', $activeStatuses)->count(),
                    'resolved' => $this->visibleReportsQuery($user)->where('status', 'resolved')->count(),
                    'today' => $this->visibleReportsQuery($user)->whereDate('created_at', now()->toDateString())->count(),
                ],
                'severityBreakdown' => [
                    'Minor' => $this->visibleReportsQuery($user)->where('severity', 'Minor')->count(),
                    'Serious' => $this->visibleReportsQuery($user)->where('severity', 'Serious')->count(),
                    'Fatal' => $this->visibleReportsQuery($user)->where('severity', 'Fatal')->count(),
                ],
                'transmissionBreakdown' => [
                    'online' => $this->visibleReportsQuery($user)->where('transmission_type', 'online')->count(),
                    'lora' => $this->visibleReportsQuery($user)->where('transmission_type', 'lora')->count(),
                ],
                'reports' => $reports,
                'mapPoints' => $reports->filter(
                    fn (IncidentReport $report): bool => $report->latitude !== null && $report->longitude !== null
                )->values(),
                'fatalAlerts' => collect(),
            ]);
        }

        $reports = IncidentReport::query()
            ->with(['assignedResponder'])
            ->whereIn('status', $activeStatuses)
            ->orderByRaw("case severity when 'Fatal' then 1 when 'Serious' then 2 when 'Minor' then 3 else 4 end")
            ->oldest('created_at')
            ->take(10)
            ->get();

        $priorityIncident = IncidentReport::query()
            ->with(['assignedResponder'])
            ->whereIn('status', $activeStatuses)
            ->orderByRaw("case severity when 'Fatal' then 1 when 'Serious' then 2 when 'Minor' then 3 else 4 end")
            ->oldest('created_at')
            ->first();

        $fatalAlerts = IncidentReport::query()
            ->where('severity', 'Fatal')
            ->whereIn('status', $activeStatuses)
            ->latest()
            ->take(5)
            ->get();

        $availableResponders = User::query()
            ->where('role', 'responder')
            ->with('responderProfile')
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->take(6)
            ->get();

        return view('dashboard', [
            'dashboardMode' => 'responder',
            'user' => $user,
            'stats' => [
                'active' => IncidentReport::query()->whereIn('status', $activeStatuses)->count(),
                'assigned_to_me' => IncidentReport::query()->where('assigned_responder_id', $user?->id)->whereIn('status', ['acknowledged', 'dispatched', 'responding'])->count(),
                'fatal' => IncidentReport::query()->where('severity', 'Fatal')->whereIn('status', $activeStatuses)->count(),
                'today' => IncidentReport::query()->whereDate('created_at', now()->toDateString())->count(),
                'lora_active' => IncidentReport::query()->where('transmission_type', 'lora')->whereDate('created_at', now()->toDateString())->count(),
            ],
            'severityBreakdown' => [
                'Minor' => IncidentReport::query()->where('severity', 'Minor')->count(),
                'Serious' => IncidentReport::query()->where('severity', 'Serious')->count(),
                'Fatal' => IncidentReport::query()->where('severity', 'Fatal')->count(),
            ],
            'transmissionBreakdown' => [
                'online' => IncidentReport::query()->where('transmission_type', 'online')->count(),
                'lora' => IncidentReport::query()->where('transmission_type', 'lora')->count(),
            ],
            'reports' => $reports,
            'fatalAlerts' => $fatalAlerts,
            'priorityIncident' => $priorityIncident,
            'dispatchRecommendation' => $priorityIncident ? $this->dispatchRecommendationFor($priorityIncident) : null,
            'availableResponders' => $availableResponders,
            'connectivity' => $this->connectivityIntelligence(),
            'mapPoints' => $reports->filter(
                fn (IncidentReport $report): bool => $report->latitude !== null && $report->longitude !== null
            ),
        ]);
    }

    private function visibleReportsQuery(?User $user): Builder
    {
        return IncidentReport::query()
            ->with(['assignedResponder'])
            ->when(
                $user?->isCivilian(),
                fn (Builder $query) => $query->where('reported_by', $user?->id)
            );
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
}
