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
@section('page_label', 'Admin Board')
@section('page_heading', 'Operations Oversight Board')
@section('page_subheading', 'Administrative governance for responder workload, civilian accounts, moderation controls, and system-wide incident oversight.')

@section('hero')
    <section class="hero-card" style="background:linear-gradient(135deg,rgba(255,255,255,.92),rgba(248,241,242,.96));border-color:rgba(201,28,33,.14);">
        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Administrative Oversight</p>
                <h2>Run the command board for governance, staffing, moderation, and system accountability.</h2>
                <p>
                    Logged in as <strong>{{ $user->name }}</strong>. This board is now intentionally different from the live Monitoring page.
                    Use this admin workspace for account oversight, queue moderation, audit review, and command-level decisions.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('monitoring') }}" class="btn btn-primary">Open Monitoring</a>
                    <a href="{{ route('civilian-accounts.index') }}" class="btn btn-secondary">Open Civilian Accounts</a>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">Open Incident Governance</a>
                    <a href="{{ route('profile.show') }}" class="btn btn-secondary">Open Admin Profile</a>
                    <button type="button" class="btn btn-secondary" data-enable-live-alerts>Enable Live Alerts</button>
                </div>
            </div>
            <div class="hero-metrics">
                <article class="metric-card">
                    <span>Civilian accounts</span>
                    <strong>{{ $stats['civilians'] }}</strong>
                    <p>Registered civilian reporters with profile access and report history.</p>
                </article>
                <article class="metric-card">
                    <span>Responder accounts</span>
                    <strong>{{ $stats['responders'] }}</strong>
                    <p>Field responders and command operators available for assignment.</p>
                </article>
                <article class="metric-card">
                    <span>Unassigned queue</span>
                    <strong>{{ $stats['unassigned_reports'] }}</strong>
                    <p>Open incidents still waiting for responder ownership.</p>
                </article>
                <article class="metric-card">
                    <span>Rejected today</span>
                    <strong>{{ $stats['rejected_today'] }}</strong>
                    <p>Reports moderated or rejected during today's oversight cycle.</p>
                </article>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="dual-grid">
        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Admin Control Center</p>
                    <h2 class="panel-title">What this board is for</h2>
                    <p class="section-copy">Use the admin board for governance and oversight, then jump to Monitoring when you need the tactical live feed.</p>
                </div>
            </div>
            <div class="stack">
                <div class="detail-card">
                    <strong>Account oversight</strong>
                    <p>Review civilian access, manage responder account readiness, and keep role separation clean across the system.</p>
                    <div class="button-row">
                        <a href="{{ route('civilian-accounts.index') }}" class="btn btn-primary">Manage Civilian Accounts</a>
                        <a href="{{ route('profile.show') }}" class="btn btn-secondary">Review Admin Profile</a>
                    </div>
                </div>
                <div class="detail-card">
                    <strong>Queue moderation</strong>
                    <p>Oversee unassigned incidents, rejected reports, and reports that need governance review before field response continues.</p>
                    <div class="button-row">
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Open Incident Feed</a>
                        <a href="{{ route('monitoring') }}" class="btn btn-secondary">Switch to Monitoring</a>
                    </div>
                </div>
                <div class="detail-card">
                    <strong>Command accountability</strong>
                    <p>Track who assigned, reopened, resolved, or rejected incidents through the audit trail and status governance panels below.</p>
                </div>
            </div>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Queue Governance</p>
                    <h2 class="panel-title">Oversight snapshot</h2>
                    <p class="section-copy">These counters focus on moderation and command health instead of duplicating the Monitoring dashboard.</p>
                </div>
            </div>
            <div class="metric-grid">
                <article class="metric-card">
                    <span>Active reports</span>
                    <strong data-live-stat="active">{{ $stats['active_reports'] }}</strong>
                    <p>Reports still inside the active operational lifecycle.</p>
                </article>
                <article class="metric-card">
                    <span>Fatal alerts</span>
                    <strong data-live-stat="fatal">{{ $stats['fatal_alerts'] }}</strong>
                    <p>Critical incidents with highest command priority.</p>
                </article>
                <article class="metric-card">
                    <span>Resolved today</span>
                    <strong>{{ $stats['resolved_today'] }}</strong>
                    <p>Incidents marked done by responders or command today.</p>
                </article>
                <article class="metric-card">
                    <span>Reports today</span>
                    <strong>{{ $stats['reports_today'] }}</strong>
                    <p>All incidents that entered the system today across channels.</p>
                </article>
            </div>
            <div class="stack" style="margin-top:14px;">
                @foreach ($statusBreakdown as $label => $count)
                    <div class="detail-card">
                        <strong>{{ $label }}</strong>
                        <p>{{ $count }} report(s) currently counted in this admin governance bucket.</p>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="triple-grid">
        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Responder Workload</p>
                    <h2 class="panel-title">Assignment load by responder</h2>
                    <p class="section-copy">This panel is admin-only and focuses on staffing balance instead of tactical live cards.</p>
                </div>
            </div>
            <div class="stack">
                @forelse ($responders as $responder)
                    <div class="detail-card">
                        <strong>{{ $responder->name }}</strong>
                        <p>{{ $responder->responderProfile?->assigned_station ?? 'Station not set' }}</p>
                        <div class="tag-row">
                            <span class="tag {{ $responder->is_admin ? 'red' : 'blue' }}">{{ $responder->is_admin ? 'Admin Responder' : 'Responder' }}</span>
                            <span class="tag neutral">{{ $responder->active_assignment_count }} active</span>
                            <span class="tag green">{{ $responder->resolved_assignment_count }} resolved</span>
                        </div>
                        <p class="detail-copy">{{ $responder->email }}</p>
                    </div>
                @empty
                    <div class="data-empty">No responder accounts are available yet.</div>
                @endforelse
            </div>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Civilian Registry</p>
                    <h2 class="panel-title">Latest civilian accounts</h2>
                    <p class="section-copy">Quick access to the newest civilian registrations and their reporting contact details.</p>
                </div>
            </div>
            <div class="stack">
                @forelse ($recentCivilians as $civilian)
                    <div class="detail-card">
                        <strong>{{ $civilian->name }}</strong>
                        <p>{{ $civilian->email }}</p>
                        <div class="tag-row">
                            <span class="tag blue">Civilian</span>
                            <span class="tag neutral">{{ $civilian->responderProfile?->phone ?? 'No contact number' }}</span>
                        </div>
                        <p class="detail-copy">{{ $civilian->responderProfile?->assigned_station ?? 'Civilian Mobile' }}</p>
                    </div>
                @empty
                    <div class="data-empty">No civilian accounts are available yet.</div>
                @endforelse
            </div>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Moderation Queue</p>
                    <h2 class="panel-title">Reports needing admin review</h2>
                    <p class="section-copy">Unassigned active reports and rejected items are surfaced here for command moderation.</p>
                </div>
            </div>
            <div class="stack">
                @forelse ($moderationQueue as $report)
                    <a href="{{ route('reports.show', $report) }}" class="detail-card">
                        <strong>{{ $report->incident_type }}</strong>
                        <p>{{ $report->report_code }} - {{ $report->location_text }}</p>
                        <div class="tag-row">
                            <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span>
                            <span class="tag {{ $statusTone($report->status) }}">{{ ucfirst($report->status) }}</span>
                        </div>
                        <div class="tag-row">
                            <span class="badge {{ $confidenceTone($report->ai_confidence) }}">Confidence {{ $report->ai_confidence !== null ? $report->ai_confidence.'%' : 'Pending' }}</span>
                            <span class="badge blue">{{ $aiSourceLabel($report->ai_source) }}</span>
                            <span class="badge {{ $aiStatusTone($report) }}">{{ $aiStatusLabel($report) }}</span>
                        </div>
                        <p class="detail-copy">{{ $report->ai_summary ?: 'AI summary is still pending for this moderation item.' }}</p>
                        <p class="detail-copy">{{ $report->assignedResponder?->name ?? 'Unassigned responder' }}</p>
                    </a>
                @empty
                    <div class="data-empty">No reports currently require admin moderation.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="dual-grid">
        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Audit Trail</p>
                    <h2 class="panel-title">Latest coordination activity</h2>
                    <p class="section-copy">Track who touched each incident and what command action happened most recently.</p>
                </div>
            </div>
            <div class="timeline">
                @forelse ($auditTrail as $entry)
                    <div class="timeline-item">
                        <span class="timeline-marker"></span>
                        <div class="timeline-copy">
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
                            <span class="meta">{{ \Illuminate\Support\Carbon::parse($entry['occurred_at'])->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="data-empty">No audit trail entries are available yet.</div>
                @endforelse
            </div>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Strategic Coverage</p>
                    <h2 class="panel-title">Admin map and dispatch oversight</h2>
                    <p class="section-copy">A higher-level command map for oversight, while the full tactical live feed stays in Monitoring.</p>
                </div>
            </div>
            <div class="stack">
                <div class="detail-card map-shell">
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
                <div class="detail-card">
                    <strong>Dispatch Recommendation Panel</strong>
                    @if ($priorityIncident && $dispatchRecommendation)
                        <p>{{ $priorityIncident->incident_type }} at {{ $priorityIncident->location_text }}</p>
                        <div class="tag-row">
                            <span class="tag red">{{ $dispatchRecommendation['priority'] }}</span>
                            <span class="tag blue">{{ $dispatchRecommendation['timer_label'] }}</span>
                        </div>
                        <p><strong>Recommended responder:</strong> {{ $dispatchRecommendation['recommended_responder'] }}</p>
                        <p><strong>Response type:</strong> {{ $dispatchRecommendation['response_type'] }}</p>
                        <a href="{{ route('reports.show', $priorityIncident) }}" class="btn btn-primary">Open Priority Incident</a>
                    @else
                        <p>No priority escalation is waiting right now.</p>
                    @endif
                </div>
                <div class="detail-card">
                    <strong>Recent oversight activity</strong>
                    <div class="stack">
                        @forelse ($recentReports->take(4) as $report)
                            <a href="{{ route('reports.show', $report) }}" class="detail-card">
                                <strong>{{ $report->incident_type }}</strong>
                                <p>{{ $report->report_code }} - {{ $priorityTimer($report) }}</p>
                                <div class="tag-row">
                                    <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span>
                                    <span class="tag {{ $statusTone($report->status) }}">{{ ucfirst($report->status) }}</span>
                                    <span class="badge {{ $confidenceTone($report->ai_confidence) }}">Confidence {{ $report->ai_confidence !== null ? $report->ai_confidence.'%' : 'Pending' }}</span>
                                </div>
                                <p class="detail-copy">{{ $aiSourceLabel($report->ai_source) }} - {{ $aiStatusLabel($report) }}</p>
                            </a>
                        @empty
                            <div class="data-empty">No recent incidents are available yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </article>
    </section>

    <section class="triple-grid">
        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Connectivity Intelligence</p>
                    <h2 class="panel-title">Command network health</h2>
                </div>
            </div>
            <div class="stack">
                <div class="detail-card"><strong>Online</strong><p>{{ $connectivity['online_status'] }}</p></div>
                <div class="detail-card"><strong>LoRa fallback</strong><p>{{ $connectivity['lora_status'] }}</p></div>
                <div class="detail-card"><strong>Internet condition</strong><p>{{ $connectivity['internet_status'] }}</p></div>
                <div class="detail-card"><strong>Gateway sync</strong><p>{{ $connectivity['gateway_status'] }}</p></div>
            </div>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Severity Distribution</p>
                    <h2 class="panel-title">Command load by severity</h2>
                </div>
            </div>
            <div class="stack">
                @foreach ($severityBreakdown as $label => $count)
                    <div class="detail-card">
                        <strong>{{ $label }}</strong>
                        <p>{{ $count }} total report(s) currently classified under this severity.</p>
                        <span class="tag {{ $tone($label) }}">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Transmission Overview</p>
                    <h2 class="panel-title">Internet and LoRa mix</h2>
                </div>
            </div>
            <div class="stack">
                <div class="detail-card">
                    <strong>Online reports</strong>
                    <p>Full payload reports with evidence, selfie verification, and richer report context.</p>
                    <span class="tag blue">{{ $transmissionBreakdown['online'] }}</span>
                </div>
                <div class="detail-card">
                    <strong>LoRa reports</strong>
                    <p>Fallback compact payloads pushed through degraded network conditions.</p>
                    <span class="tag green">{{ $transmissionBreakdown['lora'] }}</span>
                </div>
                <div class="detail-card">
                    <strong>Admin policy note</strong>
                    <p>Use Monitoring for tactical decisions, then return here for governance, moderation, and staffing control.</p>
                </div>
            </div>
        </article>
    </section>
@endsection
