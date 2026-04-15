@extends('layouts.app')

@php
    $isCivilian = ($viewerMode ?? null) === 'civilian' || auth()->user()?->isCivilian();
    $tone = static fn (string $severity): string => match ($severity) {
        'Fatal' => 'red',
        'Serious' => 'amber',
        default => 'green',
    };
    $priorityTimer = static function ($report): string {
        if ($report->created_at === null) {
            return 'Just received';
        }

        return max(1, (int) ceil($report->created_at->diffInMinutes(now()))).' min active';
    };
    $queueState = static fn (string $status): string => match ($status) {
        'resolved' => 'done',
        'rejected' => 'rejected',
        default => 'open',
    };
    $dispatchSummary = static fn (string $severity): string => match ($severity) {
        'Fatal' => 'Immediate medical and extraction support with nearest available responder.',
        'Serious' => 'Rapid field assessment and traffic or scene control support.',
        default => 'Verification patrol and scene stabilization if conditions worsen.',
    };
    $aiSourceLabel = static fn (?string $source): string => match ($source) {
        'python_model' => 'Photo AI model',
        'description_fallback' => 'Description fallback',
        'manual_override' => 'Manual override',
        null, '' => 'Source unavailable',
        default => ucwords(str_replace('_', ' ', $source)),
    };
    $aiQueueStatusLabel = static function ($report): string {
        if ($report->ai_status === 'failed') {
            return 'AI unavailable';
        }

        if ($report->ai_status === 'fallback') {
            return 'Fallback mode';
        }

        if ($report->ai_review_required) {
            return 'Responder review needed';
        }

        if ($report->ai_status === 'complete') {
            return 'Ready for triage';
        }

        return 'Pending AI review';
    };
    $aiQueueStatusTone = static function ($report): string {
        if ($report->ai_status === 'failed') {
            return 'red';
        }

        if ($report->ai_review_required) {
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
    $civilianStatusSummary = static fn (string $status): string => match ($status) {
        'resolved' => 'Responders marked this report as completed.',
        'rejected' => 'This report was reviewed and not accepted for responder action.',
        'responding' => 'Responder action is currently in progress.',
        'dispatched' => 'A responder has been dispatched to the incident.',
        'acknowledged' => 'Your report was acknowledged and is under review.',
        default => 'Your report is in the active monitoring queue.',
    };
    $civilianAttachmentSummary = static function ($report): string {
        $parts = [];

        if ($report->evidence_path) {
            $parts[] = ucfirst($report->evidence_type).' evidence attached';
        }

        if ($report->reporter_selfie_path) {
            $parts[] = 'verification selfie stored';
        }

        if ($parts === []) {
            return 'No evidence or verification selfie attached to this report.';
        }

        return ucfirst(implode(' and ', $parts)).'.';
    };
    $civilianTimeline = static function (string $status): array {
        if ($status === 'rejected') {
            return [
                ['label' => 'Submitted', 'copy' => 'Your report reached the system queue.', 'state' => 'complete', 'icon' => 'SB'],
                ['label' => 'Reviewed', 'copy' => 'The report was checked before the final decision.', 'state' => 'complete', 'icon' => 'RV'],
                ['label' => 'Rejected', 'copy' => 'Responders marked this report as rejected.', 'state' => 'current', 'icon' => 'RJ'],
            ];
        }

        $sequence = [
            'received' => ['label' => 'Submitted', 'copy' => 'Your report reached the system queue.', 'icon' => 'SB'],
            'acknowledged' => ['label' => 'Reviewed', 'copy' => 'The report was acknowledged for responder review.', 'icon' => 'RV'],
            'dispatched' => ['label' => 'Dispatched', 'copy' => 'A responder unit was dispatched to the incident.', 'icon' => 'DP'],
            'responding' => ['label' => 'Responding', 'copy' => 'Field response is actively in progress.', 'icon' => 'RS'],
            'resolved' => ['label' => 'Completed', 'copy' => 'The incident was marked as completed.', 'icon' => 'DN'],
        ];

        $keys = array_keys($sequence);
        $currentIndex = array_search($status, $keys, true);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;

        return array_map(
            static function (string $key, int $index) use ($sequence, $currentIndex): array {
                return [
                    'label' => $sequence[$key]['label'],
                    'copy' => $sequence[$key]['copy'],
                    'icon' => $sequence[$key]['icon'],
                    'state' => $index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'current' : 'upcoming'),
                ];
            },
            $keys,
            array_keys($keys)
        );
    };
    $civilianProgressPercent = static fn (string $status): int => match ($status) {
        'resolved' => 100,
        'responding' => 82,
        'dispatched' => 64,
        'acknowledged' => 42,
        'rejected' => 100,
        default => 20,
    };
    $civilianEstimatedResponse = static function ($report): array {
        $bySeverity = match ($report->severity) {
            'Fatal' => ['time' => '3-8 min', 'copy' => 'Highest priority dispatch target for life-threatening incidents.', 'tone' => 'red'],
            'Serious' => ['time' => '8-15 min', 'copy' => 'Rapid response window for major injury or road hazard incidents.', 'tone' => 'amber'],
            default => ['time' => '15-25 min', 'copy' => 'Standard review and dispatch window for lower-severity incidents.', 'tone' => 'green'],
        };

        return match ($report->status) {
            'rejected' => ['label' => 'No dispatch scheduled', 'time' => 'Review only', 'copy' => 'This report was reviewed and not queued for field deployment.', 'tone' => 'red'],
            'resolved' => ['label' => 'Response completed', 'time' => 'Closed', 'copy' => 'Responder activity for this report has already been completed.', 'tone' => 'green'],
            'responding' => ['label' => 'Responder approaching', 'time' => 'Arriving soon', 'copy' => 'A responder is already active on this incident.', 'tone' => 'green'],
            'dispatched' => ['label' => 'Responder en route', 'time' => 'Deployment started', 'copy' => 'A unit was deployed and is on the way to the incident area.', 'tone' => 'blue'],
            'acknowledged' => ['label' => 'Dispatch review in progress', 'time' => $bySeverity['time'], 'copy' => 'Your report is under active responder review for assignment.', 'tone' => 'blue'],
            default => ['label' => 'Awaiting dispatch review', 'time' => $bySeverity['time'], 'copy' => $bySeverity['copy'], 'tone' => $bySeverity['tone']],
        };
    };
    $civilianStatusNotification = static function ($report): array {
        $timestamp = $report->status_updated_at ?? $report->created_at;

        return [
            'title' => match ($report->status) {
                'resolved' => 'Report completed',
                'rejected' => 'Report rejected',
                'responding' => 'Responder is actively responding',
                'dispatched' => 'Responder dispatched',
                'acknowledged' => 'Report acknowledged',
                default => 'Report received',
            },
            'copy' => match ($report->status) {
                'resolved' => 'Responders marked this incident as completed.',
                'rejected' => 'The report was reviewed and closed without responder deployment.',
                'responding' => 'A responder is now handling your report on the field.',
                'dispatched' => 'A responder unit has been deployed to your incident.',
                'acknowledged' => 'Your report is now under responder review.',
                default => 'Your report entered the active queue successfully.',
            },
            'time' => $timestamp?->diffForHumans() ?? 'just now',
            'tone' => match ($report->status) {
                'resolved' => 'green',
                'rejected' => 'red',
                'responding', 'dispatched', 'acknowledged' => 'blue',
                default => 'neutral',
            },
        ];
    };
    $civilianRecentUpdates = $isCivilian
        ? collect($reports ?? [])->sortByDesc(static fn ($report) => $report->status_updated_at ?? $report->created_at)->take(4)->values()
        : collect();
@endphp

@section('title', $isCivilian ? 'Report History' : 'Incident Feed')
@section('page_label', $isCivilian ? 'Report History' : 'Incident Feed')
@section('page_heading', $isCivilian ? 'My Submitted Reports' : 'Live Report Management')
@section('page_subheading', $isCivilian ? 'Review only the reports you submitted, including status, evidence, verification selfie, and transmission details.' : 'Search, filter, assign, and resolve incidents while keeping evidence, verification selfies, and GPS context visible in one workspace.')

@section('hero')
    <section class="hero-card">
        <div class="hero-grid" style="grid-template-columns:minmax(0,1.1fr) minmax(300px,.9fr);">
            <div class="hero-copy">
                <p class="eyebrow">{{ $isCivilian ? 'Personal History' : 'Operations Queue' }}</p>
                <h2>{{ $isCivilian ? 'Track your own emergency submissions without responder-only controls.' : 'Manage the incident queue from one cleaner, responder-friendly workspace.' }}</h2>
                <p>
                    @if ($isCivilian)
                        This report history is limited to your civilian account. You can review your submitted incidents, attached evidence, verification selfies, and current status updates.
                    @else
                        Use filters first, scan the report cards second, then open details only when a case needs deeper coordination. All existing responder functions stay available.
                    @endif
                </p>
                <div class="hero-actions">
                    <a href="{{ route('reports.create') }}" class="btn btn-primary">{{ $isCivilian ? 'Send New Report' : 'Create Manual Report' }}</a>
                    <a href="{{ $isCivilian ? route('dashboard') : route('monitoring') }}" class="btn btn-secondary">Back To Dashboard</a>
                </div>
            </div>
            <div class="hero-metrics">
                <article class="metric-card"><span>Total reports</span><strong>{{ $stats['total'] }}</strong><p>{{ $isCivilian ? 'Reports submitted from your civilian account.' : 'All incidents stored in the system.' }}</p></article>
                <article class="metric-card"><span>Active queue</span><strong>{{ $stats['active'] }}</strong><p>{{ $isCivilian ? 'Your reports that are still active or under response.' : 'Open incidents still under monitoring or response.' }}</p></article>
                <article class="metric-card"><span>Fatal alerts</span><strong>{{ $stats['fatal'] }}</strong><p>{{ $isCivilian ? 'Your submitted reports tagged as fatal severity.' : 'Critical cases that need rapid coordination.' }}</p></article>
                <article class="metric-card"><span>Online / LoRa</span><strong>{{ $stats['online'] }} / {{ $stats['lora'] }}</strong><p>{{ $isCivilian ? 'Transport mix from your own report history.' : 'Transport balance between full payload and fallback compact reporting.' }}</p></article>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="summary-strip">
        <article class="summary-card">
            <span>Total reports</span>
            <strong>{{ $stats['total'] }}</strong>
            <p>{{ $isCivilian ? 'Reports available in your personal history.' : 'All reports currently visible in the queue.' }}</p>
        </article>
        <article class="summary-card">
            <span>Active queue</span>
            <strong>{{ $stats['active'] }}</strong>
            <p>{{ $isCivilian ? 'Reports still moving through responder review.' : 'Open incidents still waiting for completion.' }}</p>
        </article>
        <article class="summary-card">
            <span>Fatal alerts</span>
            <strong>{{ $stats['fatal'] }}</strong>
            <p>{{ $isCivilian ? 'Your fatal-severity submissions.' : 'Critical reports that need fast response.' }}</p>
        </article>
        <article class="summary-card">
            <span>Transport mix</span>
            <strong>{{ $stats['online'] }} / {{ $stats['lora'] }}</strong>
            <p>{{ $isCivilian ? 'Online and LoRa reports from your own history.' : 'Online and LoRa visibility across the current queue.' }}</p>
        </article>
    </section>

    <section class="panel">
        <div class="panel-toolbar">
            <div class="panel-heading">
                <p class="panel-kicker">Controls</p>
                <h2 class="panel-title">Search and filter</h2>
                <p class="section-copy">{{ $isCivilian ? 'Search your report history by code, location, severity, or status.' : 'Filter first so the incident feed stays focused and easy to scan.' }}</p>
            </div>
            @unless ($isCivilian)
                <div class="panel-note" style="max-width:320px;">
                    <strong>Quick workflow</strong>
                    <p class="subtle">Filter the queue, review evidence and selfie verification, then assign or update status only on the reports that need action.</p>
                </div>
            @endunless
        </div>
        <form method="GET" action="{{ route('reports.index') }}" class="form-grid">
            <div class="field"><label for="search">Search</label><input id="search" class="input" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Code, incident, location, reporter"></div>
            <div class="field"><label for="severity">Severity</label><select id="severity" name="severity"><option value="">All severities</option>@foreach (['Minor', 'Serious', 'Fatal'] as $severity)<option value="{{ $severity }}" @selected($filters['severity'] === $severity)>{{ $severity }}</option>@endforeach</select></div>
            <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach (['received', 'acknowledged', 'dispatched', 'responding', 'resolved', 'rejected'] as $status)<option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="field"><label for="transmission">Transmission</label><select id="transmission" name="transmission"><option value="">All transports</option><option value="online" @selected($filters['transmission'] === 'online')>Online</option><option value="lora" @selected($filters['transmission'] === 'lora')>LoRa</option></select></div>
            <div class="field"><label for="sort">Sort</label><select id="sort" name="sort"><option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option><option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option><option value="severity" @selected($filters['sort'] === 'severity')>Severity</option><option value="status" @selected($filters['sort'] === 'status')>Status</option></select></div>
            <div class="field"><label>&nbsp;</label><button class="btn btn-primary" type="submit">Apply Filters</button></div>
        </form>
    </section>

    @if ($isCivilian)
        <section class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Status Notifications</p>
                    <h2 class="panel-title">Recent status updates</h2>
                    <p class="section-copy">Review the latest changes to your submitted reports, including responder acknowledgements, dispatch updates, completions, or rejections.</p>
                </div>
            </div>
            <div class="preview-grid">
                @forelse ($civilianRecentUpdates as $report)
                    @php($statusNotification = $civilianStatusNotification($report))
                    <a href="{{ route('reports.show', $report) }}" class="detail-card">
                        <div class="tag-row" style="justify-content:space-between;align-items:center;">
                            <span class="tag {{ $statusNotification['tone'] }}">{{ ucfirst($report->status) }}</span>
                            <span class="incident-code">{{ $statusNotification['time'] }}</span>
                        </div>
                        <strong>{{ $statusNotification['title'] }}</strong>
                        <p>{{ $statusNotification['copy'] }}</p>
                        <p><strong>{{ $report->report_code }}</strong> | {{ $report->incident_type }}</p>
                    </a>
                @empty
                    <div class="data-empty">Status notifications will appear here after you submit your first report.</div>
                @endforelse
            </div>
        </section>
    @endif

    <section class="panel">
        <div class="panel-head">
            <div class="panel-heading">
                <p class="panel-kicker">{{ $isCivilian ? 'Report History' : 'Incident Feed' }}</p>
                <h2 class="panel-title">{{ $isCivilian ? 'My current and past reports' : 'Current reports' }}</h2>
                <p class="section-copy">{{ $isCivilian ? 'Review evidence, verification selfies, and current status updates for your own reports.' : 'List, map, and split view all keep the evidence block, verification selfie, and quick status actions available.' }}</p>
            </div>
            @unless ($isCivilian)
                <div class="view-switch" data-view-toggle-controls>
                    <button type="button" class="is-active" data-view-mode="split">Split View</button>
                    <button type="button" data-view-mode="list">List View</button>
                    <button type="button" data-view-mode="map">Map View</button>
                </div>
            @endunless
        </div>

        <div class="monitor-shell" data-view-toggle data-view-mode="{{ $isCivilian ? 'list' : 'split' }}">
            <div class="incident-stack" data-view-panel="list">
                @forelse ($reports as $report)
                    @php($state = $queueState($report->status))
                    @if ($isCivilian)
                        <article class="panel" style="padding:0;overflow:hidden;border-radius:28px;">
                            <div style="display:grid;grid-template-columns:10px minmax(0,1fr);min-height:100%;">
                                <div class="incident-rail severity-{{ $report->severity }}"></div>
                                <div style="padding:22px;display:grid;gap:18px;">
                                    <div class="incident-headline">
                                        <div class="stack" style="gap:10px;">
                                            <div class="tag-row">
                                                <span class="incident-code">{{ $report->report_code }}</span>
                                                <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span>
                                                <span class="tag blue">{{ ucfirst($report->status) }}</span>
                                                <span class="tag neutral">{{ strtoupper($report->transmission_type ?: 'online') }}</span>
                                            </div>
                                            <div>
                                                <h3 class="incident-title">{{ $report->incident_type }}</h3>
                                                <p class="detail-copy"><strong>{{ $report->location_text }}</strong> | Submitted {{ optional($report->created_at)->format('M d, Y h:i A') ?? 'Pending' }}</p>
                                            </div>
                                        </div>
                                        <div class="detail-card" style="min-width:min(100%,240px);max-width:280px;">
                                            <strong>Track this report</strong>
                                            <p>{{ $civilianStatusSummary($report->status) }}</p>
                                        </div>
                                    </div>
                                    <p class="incident-summary">{{ \Illuminate\Support\Str::limit($report->description, 190) }}</p>

                                    <div class="metric-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                                        <article class="detail-card">
                                            <strong>Current status</strong>
                                            <span class="tag {{ $state === 'done' ? 'green' : ($state === 'rejected' ? 'red' : 'blue') }}">{{ ucfirst($state) }}</span>
                                            <p>{{ $civilianStatusSummary($report->status) }}</p>
                                        </article>
                                        <article class="detail-card">
                                            <strong>Severity</strong>
                                            <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span>
                                            <p>AI-assisted severity for this submission.</p>
                                        </article>
                                        <article class="detail-card">
                                            <strong>Transport</strong>
                                            <span class="tag neutral">{{ strtoupper($report->transmission_type ?: 'online') }}</span>
                                            <p>{{ $report->transmission_type === 'lora' ? 'Compact fallback route used for delivery.' : 'Full online report delivery path.' }}</p>
                                        </article>
                                        <article class="detail-card">
                                            <strong>Attachments</strong>
                                            <p>{{ $civilianAttachmentSummary($report) }}</p>
                                        </article>
                                        @php($estimatedResponse = $civilianEstimatedResponse($report))
                                        <article class="detail-card">
                                            <strong>Estimated response time</strong>
                                            <span class="tag {{ $estimatedResponse['tone'] }}">{{ $estimatedResponse['time'] }}</span>
                                            <p><strong>{{ $estimatedResponse['label'] }}</strong></p>
                                            <p>{{ $estimatedResponse['copy'] }}</p>
                                        </article>
                                    </div>

                                    <div class="detail-card" style="background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(245,249,255,.96));border-color:rgba(32,104,174,.14);">
                                        <strong>Status timeline</strong>
                                        <p>{{ $civilianStatusSummary($report->status) }}</p>
                                        <div class="stack" style="gap:8px;">
                                            <div class="tag-row" style="justify-content:space-between;align-items:center;">
                                                <span class="tag blue">Progress tracker</span>
                                                <span class="incident-code">{{ $civilianProgressPercent($report->status) }}% complete</span>
                                            </div>
                                            <div style="height:12px;border-radius:999px;background:rgba(15,31,47,.08);overflow:hidden;">
                                                <div style="height:100%;width:{{ $civilianProgressPercent($report->status) }}%;border-radius:999px;background:{{ $report->status === 'rejected' ? 'linear-gradient(135deg,var(--danger),#ea6c6c)' : ($report->status === 'resolved' ? 'linear-gradient(135deg,var(--green),#6dd4a4)' : 'linear-gradient(135deg,var(--blue),#7ab8ef)') }};"></div>
                                            </div>
                                        </div>
                                        <div class="timeline">
                                            @foreach ($civilianTimeline($report->status) as $step)
                                                <div class="timeline-item">
                                                    <div class="timeline-marker" style="{{ $step['state'] === 'complete' ? 'background:var(--green);box-shadow:0 0 0 8px rgba(31,157,104,.12);' : ($step['state'] === 'current' ? 'background:var(--blue);box-shadow:0 0 0 8px rgba(32,104,174,.12);' : 'background:rgba(15,31,47,.16);box-shadow:none;') }}"></div>
                                                    <div class="timeline-copy">
                                                        <div class="tag-row" style="justify-content:space-between;align-items:center;">
                                                            <span class="meta">{{ strtoupper($step['state']) }}</span>
                                                            <span class="tag {{ $step['state'] === 'complete' ? 'green' : ($step['state'] === 'current' ? ($report->status === 'rejected' ? 'red' : 'blue') : 'neutral') }}" style="min-width:48px;">{{ $step['icon'] }}</span>
                                                        </div>
                                                        <strong>{{ $step['label'] }}</strong>
                                                        <p>{{ $step['copy'] }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="preview-grid">
                                        <div class="preview-card">
                                            <strong>Evidence</strong>
                                            @if ($report->evidence_path)
                                                @if ($report->evidence_type === 'photo')
                                                    <button type="button" class="media-trigger" data-media-viewer-trigger data-media-type="photo" data-media-src="{{ route('reports.evidence', $report) }}" data-media-title="{{ $report->report_code }} - {{ $report->evidence_original_name ?: 'Photo evidence' }}">
                                                        <img src="{{ route('reports.evidence', $report) }}" alt="Photo evidence for {{ $report->report_code }}" class="preview-thumb">
                                                        <span>Click to enlarge photo</span>
                                                    </button>
                                                @elseif ($report->evidence_type === 'video')
                                                    <button type="button" class="media-trigger" data-media-viewer-trigger data-media-type="video" data-media-src="{{ route('reports.evidence', $report) }}" data-media-title="{{ $report->report_code }} - {{ $report->evidence_original_name ?: 'Video evidence' }}">
                                                        <video preload="metadata" playsinline muted class="preview-thumb"><source src="{{ route('reports.evidence', $report) }}">Your browser does not support embedded video playback.</video>
                                                        <span>Click to enlarge video</span>
                                                    </button>
                                                @endif
                                                <p>{{ $report->evidence_original_name ?: 'Attached file' }}</p>
                                            @elseif ($report->transmission_type === 'lora')
                                                <p>Compact only. LoRa fallback reports do not include picture or video payloads.</p>
                                            @else
                                                <p>No photo or video evidence attached to this report.</p>
                                            @endif
                                        </div>

                                        <div class="preview-card">
                                            <strong>Verification Selfie</strong>
                                            @if ($report->reporter_selfie_path)
                                                <button type="button" class="media-trigger" data-media-viewer-trigger data-media-type="photo" data-media-src="{{ route('reports.selfie', $report) }}" data-media-title="{{ $report->report_code }} - {{ $report->reporter_selfie_original_name ?: 'Verification selfie' }}">
                                                    <img src="{{ route('reports.selfie', $report) }}" alt="Verification selfie for {{ $report->report_code }}" class="preview-thumb" style="max-height:220px;">
                                                    <span>Click to enlarge selfie</span>
                                                </button>
                                                <p>{{ $report->reporter_selfie_original_name ?: 'Verification selfie' }}</p>
                                            @else
                                                <p>No verification selfie stored for this report.</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="detail-card" style="background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(232,244,255,.96));border-color:rgba(32,104,174,.16);">
                                        <strong>My Report Actions</strong>
                                        <p>Review the full report details, AI severity result, transmission status, or remove this report from your history.</p>
                                        <div class="action-row">
                                            <a href="{{ route('reports.show', $report) }}" class="action-btn secondary">Open Details</a>
                                            <a href="{{ route('reports.severity', $report) }}" class="action-btn secondary">AI Severity</a>
                                            <a href="{{ route('reports.transmissions', $report) }}" class="action-btn secondary">Transmission</a>
                                            <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Delete this report from your history?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn danger">Delete Report</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @else
                        <article class="incident-card">
                            <div class="incident-rail severity-{{ $report->severity }}"></div>
                            <div class="incident-meta">
                                <span class="incident-code">{{ $report->report_code }}</span>
                                <strong class="incident-time">{{ $priorityTimer($report) }}</strong>
                                <div class="tag-row">
                                    <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span>
                                    <span class="tag blue">{{ ucfirst($report->status) }}</span>
                                    <span class="tag neutral">{{ strtoupper($report->transmission_type ?: 'online') }}</span>
                                </div>
                            </div>
                            <div class="incident-content">
                                <div class="incident-headline">
                                    <div>
                                        <h3 class="incident-title">{{ $report->incident_type }}</h3>
                                        <p class="detail-copy"><strong>{{ $report->location_text }}</strong> | {{ $report->reporter_name ?: 'Unknown reporter' }}</p>
                                    </div>
                                    <div class="tag-row">
                                        @if ($report->evidence_path)
                                            <span class="tag green">{{ ucfirst($report->evidence_type) }} Attached</span>
                                        @endif
                                        @if ($report->reporter_selfie_path)
                                            <span class="tag neutral">Selfie Verified</span>
                                        @endif
                                        <span class="tag {{ $state === 'done' ? 'green' : ($state === 'rejected' ? 'red' : 'blue') }}">{{ ucfirst($state) }}</span>
                                    </div>
                                </div>
                                <p class="incident-summary">{{ \Illuminate\Support\Str::limit($report->description, 170) }}</p>

                                <div class="preview-grid">
                                    <div class="preview-card">
                                        <strong>AI quick read</strong>
                                        <div class="tag-row">
                                            <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }} output</span>
                                            <span class="badge {{ $confidenceTone($report->ai_confidence) }}">Confidence {{ $report->ai_confidence !== null ? $report->ai_confidence.'%' : 'Pending' }}</span>
                                            <span class="badge blue">{{ $aiSourceLabel($report->ai_source) }}</span>
                                            <span class="badge {{ $aiQueueStatusTone($report) }}">{{ $aiQueueStatusLabel($report) }}</span>
                                        </div>
                                        <p>{{ $report->ai_summary ?: 'AI summary is still pending for this incident.' }}</p>
                                        <p><strong>Model:</strong> {{ $report->ai_model_name ? $report->ai_model_name.($report->ai_model_version ? ' v'.$report->ai_model_version : '') : 'Model pending' }}</p>
                                    </div>

                                    <div class="preview-card">
                                        <strong>Evidence</strong>
                                        @if ($report->evidence_path)
                                            @if ($report->evidence_type === 'photo')
                                                <button type="button" class="media-trigger" data-media-viewer-trigger data-media-type="photo" data-media-src="{{ route('reports.evidence', $report) }}" data-media-title="{{ $report->report_code }} - {{ $report->evidence_original_name ?: 'Photo evidence' }}">
                                                    <img src="{{ route('reports.evidence', $report) }}" alt="Photo evidence for {{ $report->report_code }}" class="preview-thumb">
                                                    <span>Click to enlarge photo</span>
                                                </button>
                                            @elseif ($report->evidence_type === 'video')
                                                <button type="button" class="media-trigger" data-media-viewer-trigger data-media-type="video" data-media-src="{{ route('reports.evidence', $report) }}" data-media-title="{{ $report->report_code }} - {{ $report->evidence_original_name ?: 'Video evidence' }}">
                                                    <video preload="metadata" playsinline muted class="preview-thumb"><source src="{{ route('reports.evidence', $report) }}">Your browser does not support embedded video playback.</video>
                                                    <span>Click to enlarge video</span>
                                                </button>
                                            @endif
                                            <p>{{ $report->evidence_original_name ?: 'Attached file' }}</p>
                                        @elseif ($report->transmission_type === 'lora')
                                            <p>Compact only. LoRa fallback reports do not include picture or video payloads.</p>
                                        @else
                                            <p>None</p>
                                        @endif
                                    </div>

                                    <div class="preview-card">
                                        <strong>Verification Selfie</strong>
                                        @if ($report->reporter_selfie_path)
                                            <button type="button" class="media-trigger" data-media-viewer-trigger data-media-type="photo" data-media-src="{{ route('reports.selfie', $report) }}" data-media-title="{{ $report->report_code }} - {{ $report->reporter_selfie_original_name ?: 'Verification selfie' }}">
                                                <img src="{{ route('reports.selfie', $report) }}" alt="Verification selfie for {{ $report->report_code }}" class="preview-thumb" style="max-height:220px;">
                                                <span>Click to enlarge selfie</span>
                                            </button>
                                            <p>{{ $report->reporter_selfie_original_name ?: 'Verification selfie' }}</p>
                                        @else
                                            <p>None</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="dual-grid" style="grid-template-columns:minmax(260px,.9fr) minmax(0,1.1fr);">
                                    <div class="detail-card">
                                        <strong>Assigned</strong>
                                        <form method="POST" action="{{ route('reports.coordination', $report) }}" class="stack">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $report->status }}">
                                            <select name="assigned_responder_id">
                                                <option value="">Unassigned</option>
                                                @foreach ($responders as $responder)
                                                    <option value="{{ $responder->id }}" @selected($report->assigned_responder_id == $responder->id)>{{ $responder->name }} - {{ $responder->is_admin ? 'Admin Responder' : 'Responder' }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-secondary">Save Assign</button>
                                        </form>
                                    </div>

                                    <div class="detail-card incident-actions">
                                        <strong>Quick actions</strong>
                                        <p>{{ $dispatchSummary($report->severity) }} You can also remove this report from the active queue if it should no longer remain in the system.</p>
                                        <div class="action-row">
                                            <form method="POST" action="{{ route('reports.coordination', $report) }}">@csrf<input type="hidden" name="assigned_responder_id" value="{{ $report->assigned_responder_id }}"><button type="submit" name="status" value="open" class="action-btn secondary">Set Open</button></form>
                                            <form method="POST" action="{{ route('reports.coordination', $report) }}">@csrf<input type="hidden" name="assigned_responder_id" value="{{ $report->assigned_responder_id }}"><button type="submit" name="status" value="done" class="action-btn primary">Set Done</button></form>
                                            <form method="POST" action="{{ route('reports.coordination', $report) }}">@csrf<input type="hidden" name="assigned_responder_id" value="{{ $report->assigned_responder_id }}"><button type="submit" name="status" value="reject" class="action-btn danger">Set Reject</button></form>
                                            <a href="{{ route('reports.show', $report) }}" class="action-btn secondary">Open Details</a>
                                            <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Delete this report from the queue?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn danger">Delete Report</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endif
                @empty
                    @if ($isCivilian)
                        <div class="panel" style="background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(231,243,255,.95));border-color:rgba(32,104,174,.16);">
                            <div class="hero-grid" style="grid-template-columns:minmax(0,1.1fr) minmax(260px,.9fr);align-items:center;">
                                <div class="hero-copy">
                                    <p class="eyebrow">No Report History Yet</p>
                                    <h2>Your submitted reports will appear here once you send an emergency report.</h2>
                                    <p>If you have not submitted a report yet, start with the civilian emergency form. If you already sent one, try clearing your filters or refreshing the page.</p>
                                    <div class="hero-actions">
                                        <a href="{{ route('reports.create') }}" class="btn btn-primary">Send New Report</a>
                                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back To Dashboard</a>
                                    </div>
                                </div>
                                <div class="metric-grid" style="grid-template-columns:1fr;">
                                    <article class="detail-card">
                                        <strong>What you will see here</strong>
                                        <p>Status progress, evidence preview, verification selfie, AI severity result, and transmission updates for your own reports only.</p>
                                    </article>
                                    <article class="detail-card">
                                        <strong>Quick reminder</strong>
                                        <p>Use online mode for full media delivery, or LoRa fallback when internet access is weak.</p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="data-empty">No reports matched the current filters.</div>
                    @endif
                @endforelse
            </div>

            @unless ($isCivilian)
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

                    <div class="detail-card">
                        <span class="eyebrow">Dispatch Recommendation</span>
                        @if ($priorityIncident && $dispatchRecommendation)
                            <strong>{{ $priorityIncident->incident_type }}</strong>
                            <p>{{ $dispatchRecommendation['summary'] ?? $dispatchSummary($priorityIncident->severity) }}</p>
                            <div class="tag-row">
                                <span class="tag red">{{ $dispatchRecommendation['priority'] }}</span>
                                <span class="tag blue">{{ $dispatchRecommendation['timer_label'] }}</span>
                            </div>
                            <p><strong>Recommended responder:</strong> {{ $dispatchRecommendation['recommended_responder'] }}</p>
                            <p><strong>Response type:</strong> {{ $dispatchRecommendation['response_type'] }}</p>
                        @else
                            <p>No active priority incident is waiting right now.</p>
                        @endif
                    </div>

                    <div class="detail-card">
                        <strong>Latest fatal incidents</strong>
                        <div class="stack">
                            @forelse ($fatalAlerts as $alert)
                                <a href="{{ route('reports.show', $alert) }}" class="detail-card"><strong>{{ $alert->incident_type }}</strong><p>{{ $alert->location_text }}</p><span class="tag red">{{ $priorityTimer($alert) }}</span></a>
                            @empty
                                <div class="data-empty">No fatal alerts are active right now.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endunless
        </div>
    </section>
@endsection
