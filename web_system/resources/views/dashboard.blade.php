@extends('layouts.app')

@php
    $isCivilian = ($dashboardMode ?? null) === 'civilian' || auth()->user()?->isCivilian();
    $tone = static fn (string $severity): string => match ($severity) {
        'Fatal' => 'red',
        'Serious' => 'amber',
        default => 'green',
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
    $priorityTimer = static function ($report): string {
        if ($report->created_at === null) {
            return 'Just received';
        }

        return max(1, (int) ceil($report->created_at->diffInMinutes(now()))).' min active';
    };
@endphp

@section('title', $isCivilian ? 'Civilian Dashboard' : 'Responder Dashboard')
@section('page_label', $isCivilian ? 'Civilian Dashboard' : 'Monitoring')
@section('page_heading', $isCivilian ? 'Civilian Reporting Home' : 'Live Incident Feed')
@section('page_subheading', $isCivilian ? 'Submit a new emergency report, review your report history, and manage your civilian account from one reporting workspace.' : 'Clean, responder-friendly monitoring for live incidents, dispatch priorities, and field communication status.')

@section('hero')
    @if ($isCivilian)
        <section class="hero-card" style="background:linear-gradient(135deg,rgba(255,255,255,.94),rgba(236,245,255,.98));border-color:rgba(32,104,174,.14);">
            <div class="hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Civilian Home</p>
                    <h2>Report fast, follow your updates easily, and keep the important actions visible first.</h2>
                    <p>
                        Logged in as <strong>{{ $user?->name }}</strong>. This civilian screen is trimmed down for phone use so the main tasks stay easy to find:
                        send a report, check your latest status, update your account, and confirm device readiness before you go.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('reports.create') }}" class="btn btn-primary">Send Emergency Report</a>
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Open Report History</a>
                        <a href="{{ route('profile.show') }}" class="btn btn-secondary">Open Civilian Profile</a>
                    </div>
                </div>
                <div class="stack">
                    <div class="hero-metrics">
                        <article class="metric-card"><span>Total reports</span><strong>{{ $stats['total'] }}</strong><p>Reports already submitted from your civilian account.</p></article>
                        <article class="metric-card"><span>Active</span><strong>{{ $stats['active'] }}</strong><p>Reports still waiting for responder action or monitoring.</p></article>
                        <article class="metric-card"><span>Resolved</span><strong>{{ $stats['resolved'] }}</strong><p>Incidents already completed or cleared from the queue.</p></article>
                        <article class="metric-card"><span>Today</span><strong>{{ $stats['today'] }}</strong><p>Reports sent from your account today.</p></article>
                    </div>
                    <article class="civilian-hero-callout">
                        <p class="eyebrow" style="color:rgba(255,255,255,.72);">Before You Send</p>
                        <h3>Four things must be ready first.</h3>
                        <ul>
                            <li>Capture a clear scene photo.</li>
                            <li>Take your verification selfie.</li>
                            <li>Lock the GPS on your current location.</li>
                            <li>Add a short and direct description.</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>
    @else
        <section class="hero-card">
            <div class="hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Responder Command Deck</p>
                    <h2>Monitor incoming incidents, prioritize severity, and coordinate response without losing live context.</h2>
                    <p>
                        Logged in as <strong>{{ $user?->name }}</strong>. The monitoring page is now cleaner and more task-focused:
                        first the current workload, then the live queue, then dispatch and connectivity guidance.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('reports.index') }}" class="btn btn-primary">Open Full Incident Feed</a>
                        <a href="{{ route('reports.create') }}" class="btn btn-secondary">Create Manual Report</a>
                        <button type="button" class="btn btn-secondary" data-enable-live-alerts>Enable Live Alerts</button>
                    </div>
                </div>
                <div class="hero-metrics">
                    <article class="metric-card"><span>Active incidents</span><strong data-live-stat="active">{{ $stats['active'] }}</strong><p>Current open queue requiring field awareness.</p></article>
                    <article class="metric-card"><span>Assigned to you</span><strong>{{ $stats['assigned_to_me'] }}</strong><p>Incidents already aligned to your responder account.</p></article>
                    <article class="metric-card"><span>Fatal alerts</span><strong data-live-stat="fatal">{{ $stats['fatal'] }}</strong><p>Priority one cases demanding rapid dispatch support.</p></article>
                    <article class="metric-card"><span>Today / LoRa</span><strong data-live-stat="today">{{ $stats['today'] }}</strong><p>{{ $stats['lora_active'] }} fallback reports transmitted over LoRa today.</p></article>
                </div>
            </div>
        </section>
    @endif
@endsection

@section('content')
    @if ($isCivilian)
        <section class="civilian-shell">
            <section class="summary-strip">
                <a href="{{ route('reports.create') }}" class="summary-card">
                    <span>Quick action</span>
                    <strong>Send Report</strong>
                    <p>Open the camera-first emergency form immediately.</p>
                </a>
                <a href="{{ route('reports.index') }}" class="summary-card">
                    <span>My history</span>
                    <strong>{{ $stats['total'] }}</strong>
                    <p>Review your submitted incidents and latest statuses.</p>
                </a>
                <a href="{{ route('profile.show') }}" class="summary-card">
                    <span>Profile</span>
                    <strong>Account</strong>
                    <p>Update your identity, photo, and contact details.</p>
                </a>
                <a href="{{ route('settings.readiness') }}" class="summary-card">
                    <span>Readiness</span>
                    <strong>Device Check</strong>
                    <p>Confirm camera, GPS, and notification access before reporting.</p>
                </a>
            </section>

            <section class="civilian-home-grid">
                <article class="panel">
                    <div class="panel-head">
                        <div class="panel-heading">
                            <p class="panel-kicker">Quick Actions</p>
                            <h2 class="panel-title">Civilian emergency access</h2>
                            <p class="section-copy">The civilian workspace now keeps the most important actions in one clean mobile-first area, so you do not need to hunt through crowded cards.</p>
                        </div>
                    </div>
                    <div class="civilian-action-grid">
                        <a href="{{ route('reports.create') }}" class="civilian-action-card primary">
                            <span class="civilian-kicker">Action 01</span>
                            <strong>Send emergency report</strong>
                            <p>Open the capture-first form for photo, selfie, GPS, and short description.</p>
                        </a>
                        <a href="{{ route('reports.index') }}" class="civilian-action-card">
                            <span class="civilian-kicker">Action 02</span>
                            <strong>Check my report history</strong>
                            <p>Review your current status, report codes, and latest responder updates.</p>
                        </a>
                        <a href="{{ route('profile.show') }}" class="civilian-action-card">
                            <span class="civilian-kicker">Action 03</span>
                            <strong>Update my civilian profile</strong>
                            <p>Fix your photo, contact details, and identity information before reporting again.</p>
                        </a>
                        <a href="{{ route('settings.readiness') }}" class="civilian-action-card">
                            <span class="civilian-kicker">Action 04</span>
                            <strong>Run device readiness check</strong>
                            <p>Confirm camera, location, notifications, and online status before an emergency happens.</p>
                        </a>
                    </div>
                </article>

                <div class="stack">
                    <article class="civilian-helper-card">
                        <strong>What you need before sending</strong>
                        <ul class="civilian-checklist">
                            <li class="civilian-check-item">Scene photo captured clearly</li>
                            <li class="civilian-check-item">Verification selfie ready</li>
                            <li class="civilian-check-item">GPS locked on your location</li>
                            <li class="civilian-check-item">Short description completed</li>
                        </ul>
                    </article>
                    <article class="civilian-helper-card">
                        <strong>Fast reminders</strong>
                        <div class="civilian-pill-list">
                            <span class="civilian-pill">Phone friendly layout</span>
                            <span class="civilian-pill">iOS and Android ready</span>
                            <span class="civilian-pill">Camera-first reporting</span>
                            <span class="civilian-pill">Offline drafts available</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-heading">
                        <p class="panel-kicker">Latest Reports</p>
                        <h2 class="panel-title">Your most recent submissions</h2>
                        <p class="section-copy">This list is intentionally lighter than the responder queue. It keeps status, severity, location, and quick actions visible without overloading the screen.</p>
                    </div>
                </div>
                <div class="civilian-history-stack">
                    @forelse ($reports as $report)
                        @php
                            $progress = match ($report->status) {
                                'resolved', 'rejected' => 100,
                                'responding' => 82,
                                'dispatched' => 64,
                                'acknowledged' => 42,
                                default => 20,
                            };
                            $progressClass = $report->status === 'resolved'
                                ? 'is-complete'
                                : ($report->status === 'rejected' ? 'is-rejected' : '');
                            $statusSummary = match ($report->status) {
                                'resolved' => 'Completed by responders',
                                'rejected' => 'Reviewed and rejected',
                                'responding' => 'Responder is on the field',
                                'dispatched' => 'Responder unit already dispatched',
                                'acknowledged' => 'Under responder review',
                                default => 'Waiting in active queue',
                            };
                        @endphp
                        <article class="civilian-history-card">
                            <div class="civilian-history-top">
                                <div class="civilian-history-meta">
                                    <div class="tag-row">
                                        <span class="incident-code">{{ $report->report_code }}</span>
                                        <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span>
                                        <span class="tag blue">{{ ucfirst($report->status) }}</span>
                                    </div>
                                    <div>
                                        <h3 class="civilian-history-title">{{ $report->incident_type }}</h3>
                                        <p><strong>{{ $report->location_text }}</strong> | Submitted {{ optional($report->created_at)->format('M d, Y h:i A') ?? 'Pending' }}</p>
                                    </div>
                                </div>
                                <span class="tag neutral">{{ strtoupper($report->transmission_type ?: 'online') }}</span>
                            </div>

                            <p>{{ \Illuminate\Support\Str::limit($report->description, 165) }}</p>

                            <div class="civilian-report-stats">
                                <article class="civilian-stat-card">
                                    <span>Status</span>
                                    <strong>{{ $statusSummary }}</strong>
                                </article>
                                <article class="civilian-stat-card">
                                    <span>Attachments</span>
                                    <strong>
                                        @if ($report->evidence_path && $report->reporter_selfie_path)
                                            Evidence + selfie ready
                                        @elseif ($report->evidence_path)
                                            Evidence attached
                                        @elseif ($report->reporter_selfie_path)
                                            Selfie verified
                                        @else
                                            No media stored
                                        @endif
                                    </strong>
                                </article>
                                <article class="civilian-stat-card">
                                    <span>Last activity</span>
                                    <strong>{{ optional($report->status_updated_at ?? $report->created_at)->diffForHumans() ?? 'Just now' }}</strong>
                                </article>
                            </div>

                            <div class="civilian-progress-block">
                                <div class="tag-row" style="justify-content:space-between;align-items:center;">
                                    <span class="incident-code">Progress</span>
                                    <span class="tag blue">{{ $progress }}% complete</span>
                                </div>
                                <div class="civilian-progress-track">
                                    <div class="civilian-progress-fill {{ $progressClass }}" style="width:{{ $progress }}%;"></div>
                                </div>
                            </div>

                            <div class="civilian-history-actions">
                                <a href="{{ route('reports.show', $report) }}" class="btn btn-primary">Open Details</a>
                                <a href="{{ route('reports.severity', $report) }}" class="btn btn-secondary">AI Severity</a>
                                <a href="{{ route('reports.transmissions', $report) }}" class="btn btn-secondary">Transmission</a>
                            </div>
                        </article>
                    @empty
                        <div class="civilian-empty-state">
                            <strong>No reports yet</strong>
                            <p>You have not submitted any reports yet. Start with the camera-first emergency form when you need it.</p>
                            <a href="{{ route('reports.create') }}" class="btn btn-primary">Send My First Report</a>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="dual-grid">
                <article class="panel">
                    <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Severity Summary</p><h2 class="panel-title">My report severity mix</h2></div></div>
                    <div class="summary-strip">
                        @foreach ($severityBreakdown as $label => $count)
                            <article class="summary-card">
                                <span>{{ $label }}</span>
                                <strong>{{ $count }}</strong>
                                <p>Reports from your civilian account currently tagged with this severity.</p>
                            </article>
                        @endforeach
                    </div>
                </article>

                <article class="panel">
                    <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Transmission Summary</p><h2 class="panel-title">Online vs LoRa history</h2></div></div>
                    <div class="summary-strip">
                        <article class="summary-card">
                            <span>Online reports</span>
                            <strong>{{ $transmissionBreakdown['online'] }}</strong>
                            <p>Full payload submissions with richer evidence and verification context.</p>
                        </article>
                        <article class="summary-card">
                            <span>LoRa fallback</span>
                            <strong>{{ $transmissionBreakdown['lora'] }}</strong>
                            <p>Compact submissions sent when network conditions required fallback transmission.</p>
                        </article>
                    </div>
                </article>
            </section>
        </section>
    @else
        <section class="summary-strip">
            <article class="summary-card">
                <span>Active incidents</span>
                <strong data-live-stat="active">{{ $stats['active'] }}</strong>
                <p>Open reports that still need responder attention.</p>
            </article>
            <article class="summary-card">
                <span>Assigned to you</span>
                <strong>{{ $stats['assigned_to_me'] }}</strong>
                <p>Incidents currently aligned to your responder account.</p>
            </article>
            <article class="summary-card">
                <span>Fatal alerts</span>
                <strong data-live-stat="fatal">{{ $stats['fatal'] }}</strong>
                <p>Highest-priority cases that need quick action.</p>
            </article>
            <article class="summary-card">
                <span>LoRa today</span>
                <strong>{{ $stats['lora_active'] }}</strong>
                <p>Fallback transmissions received through the mesh network today.</p>
            </article>
        </section>

        <section class="workspace-grid">
            <article class="panel">
                <div class="panel-toolbar">
                    <div class="panel-heading">
                        <p class="panel-kicker">Monitoring</p>
                        <h2 class="panel-title">Live incident monitoring</h2>
                        <p class="section-copy">Scan the queue first, then switch to map or split view only when you need location context.</p>
                    </div>
                    <div class="toolbar-actions">
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Open Incident Feed</a>
                        <div class="view-switch" data-view-toggle-controls>
                            <button type="button" class="is-active" data-view-mode="split">Split View</button>
                            <button type="button" data-view-mode="list">List View</button>
                            <button type="button" data-view-mode="map">Map View</button>
                        </div>
                    </div>
                </div>

                <div class="monitor-shell" data-view-toggle data-view-mode="split">
                    <div class="incident-stack" data-view-panel="list" data-live-incident-list data-max-items="8">
                        @forelse ($reports as $report)
                            <a href="{{ route('reports.show', $report) }}" class="incident-card compact-live-card" data-report-id="{{ $report->id }}">
                                <div class="incident-rail severity-{{ $report->severity }}"></div>
                                <div class="incident-meta">
                                    <span class="incident-code">{{ $report->report_code }}</span>
                                    <strong class="incident-time">{{ $priorityTimer($report) }}</strong>
                                    <div class="tag-row">
                                        <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span>
                                        <span class="tag blue">{{ ucfirst($report->status) }}</span>
                                    </div>
                                </div>
                                <div class="incident-content">
                                    <div class="incident-headline">
                                        <div>
                                            <h3 class="incident-title">{{ $report->incident_type }}</h3>
                                            <p class="detail-copy"><strong>{{ $report->location_text }}</strong> &middot; {{ $report->reporter_name ?: 'Unknown reporter' }}</p>
                                        </div>
                                        <div class="tag-row">
                                            <span class="tag neutral">{{ strtoupper($report->transmission_type ?: 'online') }}</span>
                                            @if ($report->evidence_path)
                                                <span class="tag green">{{ ucfirst($report->evidence_type) }} attached</span>
                                            @endif
                                            @if ($report->reporter_selfie_path)
                                                <span class="tag neutral">Selfie verified</span>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="incident-summary">{{ \Illuminate\Support\Str::limit($report->description, 160) }}</p>
                                    <div class="detail-card" style="background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(241,247,253,.96));border-color:rgba(15,31,47,.08);">
                                        <strong>AI quick read</strong>
                                        <div class="tag-row">
                                            <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }} output</span>
                                            <span class="badge {{ $confidenceTone($report->ai_confidence) }}">Confidence {{ $report->ai_confidence !== null ? $report->ai_confidence.'%' : 'Pending' }}</span>
                                            <span class="badge blue">{{ $aiSourceLabel($report->ai_source) }}</span>
                                            <span class="badge {{ $aiStatusTone($report) }}">{{ $aiStatusLabel($report) }}</span>
                                        </div>
                                        <p>{{ $report->ai_summary ?: 'AI summary is still pending for this incident.' }}</p>
                                        <p><strong>Model:</strong> {{ $report->ai_model_name ? $report->ai_model_name.($report->ai_model_version ? ' v'.$report->ai_model_version : '') : 'Model pending' }}</p>
                                    </div>
                                    <div class="tag-row">
                                        <span class="mini-chip">{{ $report->assignedResponder?->name ?? 'Nearest responder pending' }}</span>
                                        @if ($report->latitude !== null && $report->longitude !== null)
                                            <span class="mini-chip">GPS {{ number_format((float) $report->latitude, 4) }}, {{ number_format((float) $report->longitude, 4) }}</span>
                                        @endif
                                        <span class="mini-chip">{{ optional($report->created_at)->format('M d, Y h:i A') }}</span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="data-empty">No active incidents yet. New responder-visible reports will appear here as soon as they arrive.</div>
                        @endforelse
                    </div>

                    <div class="stack" data-view-panel="map">
                        <div class="detail-card map-shell">
                            <div class="map-canvas" data-incident-map data-points="{{ e(json_encode($mapPoints->values()->map(fn ($point) => [
                                'id' => $point->id,
                                'incident_type' => $point->incident_type,
                                'location_text' => $point->location_text,
                                'severity' => $point->severity,
                                'latitude' => $point->latitude !== null ? (float) $point->latitude : null,
                                'longitude' => $point->longitude !== null ? (float) $point->longitude : null,
                            ])->all())) }}"></div>
                        </div>

                        <div class="panel-note">
                            <strong>View guide</strong>
                            <p class="subtle">Use list view for quick triage, map view for location context, and split view when dispatch decisions need both.</p>
                        </div>
                    </div>
                </div>
            </article>

            <div class="workspace-side">
                <article class="panel">
                    <div class="panel-head">
                        <div class="panel-heading">
                            <p class="panel-kicker">Priority Incident</p>
                            <h2 class="panel-title">Dispatch recommendation</h2>
                        </div>
                    </div>
                    @if ($priorityIncident && $dispatchRecommendation)
                        <div class="detail-card">
                            <strong>{{ $priorityIncident->incident_type }}</strong>
                            <p>{{ $priorityIncident->location_text }}</p>
                            <div class="tag-row">
                                <span class="tag red">{{ $dispatchRecommendation['priority'] }}</span>
                                <span class="tag blue">{{ $dispatchRecommendation['timer_label'] }}</span>
                            </div>
                            <div class="meta-list">
                                <div class="meta-row"><strong>Recommended responder</strong><span>{{ $dispatchRecommendation['recommended_responder'] }}</span></div>
                                <div class="meta-row"><strong>Response type</strong><span>{{ $dispatchRecommendation['response_type'] }}</span></div>
                            </div>
                            <a href="{{ route('reports.show', $priorityIncident) }}" class="btn btn-primary">View Priority Incident</a>
                        </div>
                    @else
                        <div class="data-empty">No priority incident is waiting right now.</div>
                    @endif
                </article>

                <article class="panel">
                    <div class="panel-head">
                        <div class="panel-heading">
                            <p class="panel-kicker">Alert Queue</p>
                            <h2 class="panel-title">Latest fatal incidents</h2>
                        </div>
                    </div>
                    <div class="stack">
                        @forelse ($fatalAlerts as $alert)
                            <a href="{{ route('reports.show', $alert) }}" class="detail-card">
                                <strong>{{ $alert->incident_type }}</strong>
                                <p>{{ $alert->location_text }}</p>
                                <div class="tag-row">
                                    <span class="tag red">Fatal</span>
                                    <span class="tag blue">{{ ucfirst($alert->status) }}</span>
                                    <span class="tag neutral">{{ $priorityTimer($alert) }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="data-empty">No fatal alerts are active at the moment.</div>
                        @endforelse
                    </div>
                </article>

                <article class="panel">
                    <div class="panel-head">
                        <div class="panel-heading">
                            <p class="panel-kicker">Connectivity Intelligence</p>
                            <h2 class="panel-title">Field communication snapshot</h2>
                        </div>
                    </div>
                    <div class="meta-list">
                        <div class="meta-row"><strong>Online</strong><span>{{ $connectivity['online_status'] }}</span></div>
                        <div class="meta-row"><strong>LoRa fallback</strong><span>{{ $connectivity['lora_status'] }}</span></div>
                        <div class="meta-row"><strong>Internet condition</strong><span>{{ $connectivity['internet_status'] }}</span></div>
                        <div class="meta-row"><strong>Gateway</strong><span>{{ $connectivity['gateway_status'] }}</span></div>
                    </div>
                </article>

                <article class="panel">
                    <div class="panel-head">
                        <div class="panel-heading">
                            <p class="panel-kicker">Responder Roster</p>
                            <h2 class="panel-title">Available responder profiles</h2>
                        </div>
                    </div>
                    <div class="stack">
                        @forelse ($availableResponders as $responder)
                            <div class="detail-card">
                                <strong>{{ $responder->name }}</strong>
                                <p>{{ $responder->responderProfile?->assigned_station ?? 'Station not set' }}</p>
                                <span class="tag {{ $responder->is_admin ? 'red' : 'blue' }}">{{ $responder->is_admin ? 'Admin Responder' : 'Responder' }}</span>
                            </div>
                        @empty
                            <div class="data-empty">No responder roster entries are available yet.</div>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>

        <section class="dual-grid">
            <article class="panel">
                <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Severity Visualization</p><h2 class="panel-title">Current load by severity</h2></div></div>
                <div class="summary-strip">
                    @foreach ($severityBreakdown as $label => $count)
                        <article class="summary-card">
                            <span>{{ $label }}</span>
                            <strong>{{ $count }}</strong>
                            <p>Incidents currently tracked in this severity class.</p>
                        </article>
                    @endforeach
                </div>
            </article>

            <article class="panel">
                <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Transmission Mix</p><h2 class="panel-title">Online and LoRa coverage</h2></div></div>
                <div class="summary-strip">
                    <article class="summary-card">
                        <span>Online reports</span>
                        <strong>{{ $transmissionBreakdown['online'] }}</strong>
                        <p>Full payload reports with picture or video evidence.</p>
                    </article>
                    <article class="summary-card">
                        <span>LoRa reports</span>
                        <strong>{{ $transmissionBreakdown['lora'] }}</strong>
                        <p>Compact fallback reports with severity, GPS, and description.</p>
                    </article>
                </div>
            </article>
        </section>
    @endif
@endsection
