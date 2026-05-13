@extends('layouts.app')

@php
    $tone = static fn (string $severity): string => match ($severity) {
        'Fatal' => 'red',
        'Serious' => 'amber',
        default => 'green',
    };
    $statusTone = static fn (string $status): string => match ($status) {
        'rejected' => 'red',
        'resolved' => 'green',
        'responding', 'dispatched', 'acknowledged' => 'blue',
        default => 'neutral',
    };
    $priorityTimer = static function ($report): string {
        if ($report->created_at === null) {
            return 'Just received';
        }

        return max(1, (int) ceil($report->created_at->diffInMinutes(now()))).' min active';
    };
    $aiSourceLabel = static fn (?string $source): string => match ($source) {
        'python_model' => 'Photo AI model',
        'description_fallback' => 'Description fallback',
        'manual_override' => 'Manual override',
        null, '' => 'Source unavailable',
        default => ucwords(str_replace('_', ' ', $source)),
    };
    $aiStatusLabel = static function ($report): string {
        if ($report->ai_status === 'failed') {
            return 'AI unavailable';
        }

        if ($report->ai_review_required) {
            return 'Responder review needed';
        }

        if ($report->ai_status === 'fallback') {
            return 'Fallback mode';
        }

        if ($report->ai_status === 'complete') {
            return 'Ready for triage';
        }

        return 'Pending AI review';
    };
    $aiStatusTone = static function ($report): string {
        if ($report->ai_status === 'failed' || $report->ai_review_required) {
            return 'red';
        }

        if ($report->ai_status === 'fallback') {
            return 'amber';
        }

        if ($report->ai_status === 'complete') {
            return 'green';
        }

        return 'neutral';
    };
    $confidenceTone = static function ($confidence): string {
        if ($confidence === null) {
            return 'neutral';
        }

        if ($confidence >= 85) {
            return 'green';
        }

        if ($confidence >= 60) {
            return 'amber';
        }

        return 'red';
    };
@endphp

@section('title', 'Admin Dashboard')
@section('page_label', 'Administrative Oversight')
@section('page_heading', 'Admin Control Center')
@section('page_subheading', 'Simple command view for accounts, incident review, responder workload, and system readiness.')

@section('hero')
    <section class="command-hero">
        <div class="command-hero-main">
            <p class="eyebrow">Command Center</p>
            <h2>Decide what needs attention first.</h2>
            <p>Signed in as <strong>{{ $user->name }}</strong>. Use the cards below to jump directly to monitoring, incident review, accounts, or profile settings.</p>
        </div>
        <div class="command-action-grid" aria-label="Command shortcuts">
            <a href="{{ route('monitoring') }}" class="command-action primary">
                <span>01</span>
                <strong>Open Monitoring</strong>
                <small>Live tactical view</small>
            </a>
            <a href="{{ route('reports.index') }}" class="command-action">
                <span>02</span>
                <strong>Incident Feed</strong>
                <small>Review and moderate</small>
            </a>
            <a href="{{ route('civilian-accounts.index') }}" class="command-action">
                <span>03</span>
                <strong>Manage Civilian Accounts</strong>
                <small>Manage access</small>
            </a>
            <a href="{{ route('profile.show') }}" class="command-action">
                <span>04</span>
                <strong>Admin Profile</strong>
                <small>Identity and contact</small>
            </a>
            <button type="button" class="command-action" data-enable-live-alerts>
                <span>05</span>
                <strong>Live Alerts</strong>
                <small>Enable browser alerts</small>
            </button>
        </div>
    </section>
@endsection

@section('content')
    <section class="command-stat-grid" aria-label="Command summary">
        <article class="command-stat urgent">
            <span>Needs attention</span>
            <strong>{{ $stats['unassigned_reports'] }}</strong>
            <p>Unassigned incidents waiting for ownership.</p>
        </article>
        <article class="command-stat">
            <span>Active reports</span>
            <strong data-live-stat="active">{{ $stats['active_reports'] }}</strong>
            <p>Open incident lifecycle count.</p>
        </article>
        <article class="command-stat danger">
            <span>Fatal alerts</span>
            <strong data-live-stat="fatal">{{ $stats['fatal_alerts'] }}</strong>
            <p>Highest-priority dispatch cases.</p>
        </article>
        <article class="command-stat">
            <span>Resolved today</span>
            <strong>{{ $stats['resolved_today'] }}</strong>
            <p>Closed incidents for today.</p>
        </article>
        <article class="command-stat">
            <span>Civilians</span>
            <strong>{{ $stats['civilians'] }}</strong>
            <p>Registered civilian reporters.</p>
        </article>
        <article class="command-stat">
            <span>Responders</span>
            <strong>{{ $stats['responders'] }}</strong>
            <p>Responder and command users.</p>
        </article>
    </section>

    <section class="command-layout">
        <article class="panel command-focus-panel">
            <div class="panel-head command-panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Review First</p>
                    <h2 class="panel-title">Reports needing command review</h2>
                    <p class="section-copy">These are the items most likely to need action before other dashboard details.</p>
                </div>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary">Open All Reports</a>
            </div>

            <div class="command-review-list">
                @forelse ($moderationQueue as $report)
                    <a href="{{ route('reports.show', $report) }}" class="command-review-card">
                        <div class="command-review-main">
                            <span class="incident-code">{{ $report->report_code }}</span>
                            <h3>{{ $report->incident_type }}</h3>
                            <p>{{ $report->location_text }}</p>
                        </div>
                        <div class="command-review-meta">
                            <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span>
                            <span class="tag {{ $statusTone($report->status) }}">{{ ucfirst($report->status) }}</span>
                            <span class="badge {{ $confidenceTone($report->ai_confidence) }}">Confidence {{ $report->ai_confidence !== null ? $report->ai_confidence.'%' : 'Pending' }}</span>
                            <span class="badge blue">{{ $aiSourceLabel($report->ai_source) }}</span>
                            <span class="badge {{ $aiStatusTone($report) }}">{{ $aiStatusLabel($report) }}</span>
                        </div>
                        <p class="detail-copy">{{ $report->ai_summary ?: 'AI summary is still pending for this item.' }}</p>
                        <div class="command-review-footer">
                            <span>{{ $report->assignedResponder?->name ?? 'Unassigned responder' }}</span>
                            <strong>Open</strong>
                        </div>
                    </a>
                @empty
                    <div class="command-empty-state">
                        <strong>No command review needed.</strong>
                        <p>The moderation queue is clear right now.</p>
                    </div>
                @endforelse
            </div>
        </article>

        <aside class="command-side-stack">
            <article class="panel command-compact-panel">
                <div class="panel-heading">
                    <p class="panel-kicker">Priority</p>
                    <h2 class="panel-title">Dispatch recommendation</h2>
                </div>
                @if ($priorityIncident && $dispatchRecommendation)
                    <div class="command-priority-card">
                        <strong>{{ $priorityIncident->incident_type }}</strong>
                        <p>{{ $priorityIncident->location_text }}</p>
                        <div class="tag-row">
                            <span class="tag red">{{ $dispatchRecommendation['priority'] }}</span>
                            <span class="tag blue">{{ $dispatchRecommendation['timer_label'] }}</span>
                        </div>
                        <div class="meta-list">
                            <div class="meta-row"><strong>Responder</strong><span>{{ $dispatchRecommendation['recommended_responder'] }}</span></div>
                            <div class="meta-row"><strong>Response</strong><span>{{ $dispatchRecommendation['response_type'] }}</span></div>
                        </div>
                        <a href="{{ route('reports.show', $priorityIncident) }}" class="btn btn-primary">Open Priority Incident</a>
                    </div>
                @else
                    <div class="command-empty-state compact">
                        <strong>No priority escalation.</strong>
                        <p>Nothing needs immediate command escalation right now.</p>
                    </div>
                @endif
            </article>

            <article class="panel command-compact-panel">
                <div class="panel-heading">
                    <p class="panel-kicker">System</p>
                    <h2 class="panel-title">Readiness</h2>
                </div>
                <div class="command-readiness-list">
                    <div><strong>Online</strong><span>{{ $connectivity['online_status'] }}</span></div>
                    <div><strong>LoRa</strong><span>{{ $connectivity['lora_status'] }}</span></div>
                    <div><strong>Internet</strong><span>{{ $connectivity['internet_status'] }}</span></div>
                    <div><strong>Gateway</strong><span>{{ $connectivity['gateway_status'] }}</span></div>
                </div>
            </article>
        </aside>
    </section>

    <section class="command-two-column">
        <article class="panel command-compact-panel">
            <div class="panel-head command-panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Responder Workload</p>
                    <h2 class="panel-title">Who is carrying work?</h2>
                </div>
                <a href="{{ route('profile.show') }}" class="btn btn-secondary">Admin Profile</a>
            </div>
            <div class="command-person-list">
                @forelse ($responders as $responder)
                    <div class="command-person-card">
                        <div>
                            <strong>{{ $responder->name }}</strong>
                            <p>{{ $responder->responderProfile?->assigned_station ?? 'Station not set' }}</p>
                        </div>
                        <div class="tag-row">
                            <span class="tag {{ $responder->is_admin ? 'red' : 'blue' }}">{{ $responder->is_admin ? 'Admin' : 'Responder' }}</span>
                            <span class="tag neutral">{{ $responder->active_assignment_count }} active</span>
                            <span class="tag green">{{ $responder->resolved_assignment_count }} resolved</span>
                        </div>
                    </div>
                @empty
                    <div class="command-empty-state"><strong>No responders yet.</strong><p>Create responder accounts before assigning incidents.</p></div>
                @endforelse
            </div>
        </article>

        <article class="panel command-compact-panel">
            <div class="panel-head command-panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Civilian Registry</p>
                    <h2 class="panel-title">Recent civilian accounts</h2>
                </div>
                <a href="{{ route('civilian-accounts.index') }}" class="btn btn-secondary">Manage Accounts</a>
            </div>
            <div class="command-person-list">
                @forelse ($recentCivilians as $civilian)
                    <div class="command-person-card">
                        <div>
                            <strong>{{ $civilian->name }}</strong>
                            <p>{{ $civilian->email }}</p>
                        </div>
                        <div class="tag-row">
                            <span class="tag blue">Civilian</span>
                            <span class="tag neutral">{{ $civilian->responderProfile?->phone ?? 'No contact number' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="command-empty-state"><strong>No civilians yet.</strong><p>New civilian registrations will appear here.</p></div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="command-layout reverse">
        <article class="panel command-compact-panel">
            <div class="panel-head command-panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Coverage</p>
                    <h2 class="panel-title">Command map</h2>
                    <p class="section-copy">Use this for quick location context. Open Monitoring for full tactical map work.</p>
                </div>
                <a href="{{ route('monitoring') }}" class="btn btn-secondary">Open Monitoring</a>
            </div>
            <div class="map-shell command-map-shell">
                <div
                    class="map-canvas"
                    data-incident-map
                    data-points="{{ e(json_encode($mapPoints->values()->map(fn ($point) => [
                        'id' => $point->id,
                        'incident_type' => $point->incident_type,
                        'location_text' => $point->location_text,
                        'severity' => $point->severity,
                        'latitude' => $point->latitude !== null ? (float) $point->latitude : null,
                        'longitude' => $point->longitude !== null ? (float) $point->longitude : null,
                    ])->all())) }}"
                ></div>
            </div>
        </article>

        <aside class="command-side-stack">
            <article class="panel command-compact-panel">
                <div class="panel-heading">
                    <p class="panel-kicker">Status Mix</p>
                    <h2 class="panel-title">Queue by status</h2>
                </div>
                <div class="command-mini-metrics">
                    @foreach ($statusBreakdown as $label => $count)
                        <div><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                    @endforeach
                </div>
            </article>

            <article class="panel command-compact-panel">
                <div class="panel-heading">
                    <p class="panel-kicker">Severity Mix</p>
                    <h2 class="panel-title">Load by severity</h2>
                </div>
                <div class="command-mini-metrics">
                    @foreach ($severityBreakdown as $label => $count)
                        <div><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                    @endforeach
                </div>
            </article>

            <article class="panel command-compact-panel">
                <div class="panel-heading">
                    <p class="panel-kicker">Transmission</p>
                    <h2 class="panel-title">Online / LoRa</h2>
                </div>
                <div class="command-mini-metrics two">
                    <div><span>Online</span><strong>{{ $transmissionBreakdown['online'] }}</strong></div>
                    <div><span>LoRa</span><strong>{{ $transmissionBreakdown['lora'] }}</strong></div>
                </div>
            </article>
        </aside>
    </section>

    <section class="command-two-column">
        <article class="panel command-compact-panel">
            <div class="panel-heading">
                <p class="panel-kicker">Audit Trail</p>
                <h2 class="panel-title">Latest command actions</h2>
            </div>
            <div class="command-audit-list">
                @forelse ($auditTrail as $entry)
                    <div class="command-audit-card">
                        <span></span>
                        <div>
                            <strong>{{ $entry['label'] }}</strong>
                            <p>{{ $entry['incident_type'] }} - {{ $entry['report_code'] }}</p>
                            <div class="tag-row">
                                <span class="tag {{ $statusTone($entry['status']) }}">{{ ucfirst($entry['status']) }}</span>
                                <span class="tag neutral">{{ $entry['actor_name'] }} - {{ $entry['actor_role'] }}</span>
                                @if (! empty($entry['assigned_responder_name']))
                                    <span class="tag blue">{{ $entry['assigned_responder_name'] }}</span>
                                @endif
                            </div>
                            @if (! empty($entry['response_notes']))
                                <p>{{ $entry['response_notes'] }}</p>
                            @endif
                            <small>{{ \Illuminate\Support\Carbon::parse($entry['occurred_at'])->diffForHumans() }}</small>
                        </div>
                    </div>
                @empty
                    <div class="command-empty-state"><strong>No audit entries yet.</strong><p>Command activity will appear here.</p></div>
                @endforelse
            </div>
        </article>

        <article class="panel command-compact-panel">
            <div class="panel-heading">
                <p class="panel-kicker">Recent Oversight</p>
                <h2 class="panel-title">Latest incidents</h2>
            </div>
            <div class="command-review-list compact">
                @forelse ($recentReports->take(4) as $report)
                    <a href="{{ route('reports.show', $report) }}" class="command-review-card compact">
                        <div class="command-review-main">
                            <span class="incident-code">{{ $report->report_code }}</span>
                            <h3>{{ $report->incident_type }}</h3>
                            <p>{{ $priorityTimer($report) }}</p>
                        </div>
                        <div class="command-review-meta">
                            <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span>
                            <span class="tag {{ $statusTone($report->status) }}">{{ ucfirst($report->status) }}</span>
                            <span class="badge {{ $aiStatusTone($report) }}">{{ $aiStatusLabel($report) }}</span>
                        </div>
                        <p class="detail-copy">{{ $aiSourceLabel($report->ai_source) }}</p>
                    </a>
                @empty
                    <div class="command-empty-state"><strong>No incidents yet.</strong><p>New reports will appear here.</p></div>
                @endforelse
            </div>
        </article>
    </section>
@endsection
