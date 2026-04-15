@extends('layouts.app')

@php
    $isCivilian = ($viewerMode ?? null) === 'civilian' || auth()->user()?->isCivilian();
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
        if ($report->ai_status === 'fallback') {
            return 'Fallback mode';
        }

        if ($report->ai_status === 'failed') {
            return 'AI unavailable';
        }

        if ($report->ai_review_required) {
            return 'Responder review needed';
        }

        if ($report->ai_status === 'complete') {
            return 'Ready for triage';
        }

        return 'Pending AI review';
    };
    $aiStatusTone = static function ($report): string {
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
    $evidenceUrl = $report->evidence_path ? route('reports.evidence', $report) : null;
    $downloadUrl = $report->evidence_path ? route('reports.evidence', ['incidentReport' => $report, 'download' => 1]) : null;
    $selfieUrl = $report->reporter_selfie_path ? route('reports.selfie', $report) : null;
    $selfieDownloadUrl = $report->reporter_selfie_path ? route('reports.selfie', ['incidentReport' => $report, 'download' => 1]) : null;
    $priorityTimer = static function ($report): string {
        if ($report->created_at === null) {
            return 'Just received';
        }

        return max(1, (int) ceil($report->created_at->diffInMinutes(now()))).' min active';
    };
@endphp

@section('title', 'Incident Details')
@section('page_label', $isCivilian ? 'Report History' : 'Incident Details')
@section('page_heading', $report->incident_type)
@section('page_subheading', $isCivilian ? 'Review your submitted report details, evidence, verification selfie, and transmission updates.' : 'Review severity, transmission details, evidence, verification selfie, audit trail, and coordination actions from one command view.')

@section('hero')
    <section class="hero-card">
        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">{{ $isCivilian ? 'My Report Details' : 'Incident Details' }}</p>
                <h2>{{ $report->report_code }} • {{ $report->incident_type }}</h2>
                <p>
                    Reported by <strong>{{ $report->reporter_name ?: 'Unknown reporter' }}</strong> at <strong>{{ $report->location_text }}</strong>.
                    {{ $isCivilian ? 'This page is focused on your submission history only, so responder coordination controls are hidden from the civilian account view.' : 'This detail view keeps AI review, transmission status, evidence access, and responder coordination in a single operational screen.' }}
                </p>
                <div class="hero-actions">
                    <a href="{{ route('reports.severity', $report) }}" class="btn btn-primary">Open AI Severity</a>
                    <a href="{{ route('reports.transmissions', $report) }}" class="btn btn-secondary">Open Transmission Status</a>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">{{ $isCivilian ? 'Back To History' : 'Back To Feed' }}</a>
                    <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Delete this report?');" style="display:inline-flex;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Delete Report</button>
                    </form>
                </div>
            </div>
            <div class="hero-metrics">
                <article class="metric-card"><span>Severity</span><strong>{{ $report->severity }}</strong><p>Current AI-assisted severity classification.</p></article>
                <article class="metric-card"><span>Status</span><strong>{{ ucfirst($report->status) }}</strong><p>{{ $isCivilian ? 'Current progress of your submitted incident.' : 'Most recent coordination state in the queue.' }}</p></article>
                <article class="metric-card"><span>Transmission</span><strong>{{ strtoupper($report->transmission_type ?: 'online') }}</strong><p>{{ $report->channel ?: 'Internet' }}</p></article>
                <article class="metric-card"><span>Priority timer</span><strong>{{ $priorityTimer($report) }}</strong><p>Elapsed active time since the report was first created.</p></article>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="dual-grid">
        <article class="panel">
            <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Incident Data</p><h2 class="panel-title">Core details</h2></div></div>
            <div class="stack">
                <div class="detail-card"><strong>Severity</strong><span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span></div>
                <div class="detail-card"><strong>Status</strong><p>{{ ucfirst($report->status) }}</p></div>
                <div class="detail-card"><strong>Description</strong><p>{{ $report->description }}</p></div>
                <div class="detail-card"><strong>Reporter</strong><p>{{ $report->reporter_name ?: 'Unknown' }}<br>{{ $report->reporter_contact ?: 'No contact provided' }}</p></div>
                <div class="detail-card"><strong>GPS coordinates</strong><p>@if ($report->latitude !== null && $report->longitude !== null){{ number_format((float) $report->latitude, 6) }}, {{ number_format((float) $report->longitude, 6) }}@else Coordinates not available @endif</p></div>
                <div class="detail-card">
                    <strong>AI summary</strong>
                    <div class="tag-row">
                        <span class="tag {{ $tone($report->severity) }}">{{ $report->severity }} output</span>
                        <span class="badge {{ $confidenceTone($report->ai_confidence) }}">Confidence {{ $report->ai_confidence !== null ? $report->ai_confidence.'%' : 'Pending' }}</span>
                        <span class="badge blue">{{ $aiSourceLabel($report->ai_source) }}</span>
                        <span class="badge {{ $aiStatusTone($report) }}">{{ $aiStatusLabel($report) }}</span>
                    </div>
                    <p>
                        {{ $report->ai_summary ?: 'No AI summary generated.' }}
                    </p>
                    <div class="meta-list">
                        <div class="meta-row">
                            <span>AI status</span>
                            <strong>{{ $aiStatusLabel($report) }}</strong>
                        </div>
                        <div class="meta-row">
                            <span>AI source</span>
                            <strong>{{ $aiSourceLabel($report->ai_source) }}</strong>
                        </div>
                        <div class="meta-row">
                            <span>AI model</span>
                            <strong>{{ $report->ai_model_name ? $report->ai_model_name.($report->ai_model_version ? ' v'.$report->ai_model_version : '') : 'Model unavailable' }}</strong>
                        </div>
                        <div class="meta-row">
                            <span>Processed</span>
                            <strong>{{ $report->ai_processed_at ? $report->ai_processed_at->format('M d, Y h:i A') : 'Pending processing' }}</strong>
                        </div>
                    </div>
                    @if (\App\Support\AiSeverityMapper::humanizeErrorMessage($report->ai_error_message))
                        <div class="panel-note">
                            <strong>Fallback note</strong>
                            <p>{{ \App\Support\AiSeverityMapper::humanizeErrorMessage($report->ai_error_message) }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </article>

        <article class="panel">
            @if ($isCivilian)
                <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Submission Status</p><h2 class="panel-title">Your report progress</h2></div></div>
                <div class="stack">
                    <div class="detail-card"><strong>Current status</strong><p>{{ ucfirst($report->status) }}</p></div>
                    <div class="detail-card"><strong>Assigned responder</strong><p>{{ $report->assignedResponder?->name ?? 'Not yet assigned' }}</p></div>
                    <div class="detail-card"><strong>Status updated</strong><p>{{ optional($report->status_updated_at)->format('M d, Y h:i A') ?? 'Not yet updated' }}</p></div>
                    <div class="detail-card"><strong>Transmission channel</strong><p>{{ $report->channel ?: 'Internet' }}</p></div>
                </div>
            @else
                <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Emergency Coordination</p><h2 class="panel-title">Assign responder and update status</h2></div></div>
                <form method="POST" action="{{ route('reports.coordination', $report) }}" class="stack">
                    @csrf
                    <div class="field"><label for="assigned_responder_id">Assigned responder</label><select id="assigned_responder_id" name="assigned_responder_id"><option value="">Unassigned</option>@foreach ($responders as $responder)<option value="{{ $responder->id }}" @selected(old('assigned_responder_id', $report->assigned_responder_id) == $responder->id)>{{ $responder->name }} - {{ $responder->is_admin ? 'Admin Responder' : 'Responder' }}</option>@endforeach</select></div>
                    <div class="field"><label for="status">Response status</label><select id="status" name="status">@foreach (['received', 'acknowledged', 'dispatched', 'responding', 'resolved', 'rejected'] as $status)<option value="{{ $status }}" @selected(old('status', $report->status) === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                    <div class="field"><label for="response_notes">Response notes</label><textarea id="response_notes" class="textarea" name="response_notes" placeholder="Responder actions, route notes, or follow-up details">{{ old('response_notes', $report->response_notes) }}</textarea></div>
                    <button class="btn btn-primary" type="submit">Save Coordination Update</button>
                </form>
                <div class="divider"></div>
                <div class="detail-card">
                    <span class="eyebrow">Dispatch Recommendation</span>
                    <strong>{{ $dispatchRecommendation['priority'] }}</strong>
                    <p>{{ $dispatchRecommendation['summary'] }}</p>
                    <p><strong>Recommended responder:</strong> {{ $dispatchRecommendation['recommended_responder'] }}</p>
                    <p><strong>Response type:</strong> {{ $dispatchRecommendation['response_type'] }}</p>
                    <p><strong>Priority timer:</strong> {{ $dispatchRecommendation['timer_label'] }}</p>
                </div>
            @endif
        </article>
    </section>

    <section class="dual-grid">
        <article class="panel">
            <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Map Tracking</p><h2 class="panel-title">Incident location</h2></div></div>
            <div class="preview-grid">
                <div class="detail-card">
                    <strong>Incident location</strong>
                    <p>{{ $locationMeta['readable_location'] }}</p>
                    <p>
                        @if ($report->latitude !== null && $report->longitude !== null)
                            GPS {{ number_format((float) $report->latitude, 6) }}, {{ number_format((float) $report->longitude, 6) }}
                        @else
                            GPS coordinates are not available for this report.
                        @endif
                    </p>
                    <p><strong>Barangay / town:</strong> {{ $locationMeta['barangay_town'] ?: 'Not available yet' }}</p>
                    <p><strong>Submitted text:</strong> {{ $report->location_text }}</p>
                </div>
                <div class="detail-card">
                    <strong>Command center route</strong>
                    <p>{{ $locationMeta['distance_label'] ?: 'Awaiting GPS coordinates' }}</p>
                    <p><strong>Travel time:</strong> {{ $locationMeta['travel_time_label'] ?: 'Awaiting route estimate' }}</p>
                    <p><strong>Command center:</strong> {{ $commandCenter['location_text'] }}</p>
                    <p>
                        @if ($locationMeta['route_status'] === 'live')
                            Live road route is ready from {{ $commandCenter['name'] }} to the sender mobile location.
                        @else
                            {{ $locationMeta['route_notice'] ?: 'Live road routing is not available right now, so the system is showing a safe fallback estimate.' }}
                        @endif
                    </p>
                </div>
                <div class="detail-card">
                    <strong>Map actions</strong>
                    <p>{{ $isCivilian ? 'Open your submitted incident location in Google Maps if you need to review the exact pin placement.' : 'Open the sender location in Google Maps for route planning and responder navigation.' }}</p>
                    <p><strong>Red pin:</strong> Sender mobile location<br><strong>Blue pin:</strong> {{ $commandCenter['name'] }}</p>
                    <div class="action-row">
                        <a href="{{ $locationMeta['google_maps_url'] }}" class="btn btn-primary" target="_blank" rel="noopener">Open in Google Maps</a>
                        <a href="{{ $locationMeta['directions_url'] }}" class="btn btn-secondary" target="_blank" rel="noopener">Open Route Directions</a>
                        <a href="{{ $commandCenter['google_maps_url'] }}" class="btn btn-secondary" target="_blank" rel="noopener">Open Command Center</a>
                    </div>
                </div>
            </div>
            <div class="divider"></div>
            @if ($mapPoints->isNotEmpty())
                <div class="detail-card map-shell">
                    <div class="map-canvas" data-incident-map data-points="{{ e(json_encode($mapPoints->values()->map(fn ($point) => [
                        'id' => $point['id'],
                        'incident_type' => $point['incident_type'],
                        'location_text' => $point['location_text'],
                        'severity' => $point['severity'],
                        'latitude' => $point['latitude'] !== null ? (float) $point['latitude'] : null,
                        'longitude' => $point['longitude'] !== null ? (float) $point['longitude'] : null,
                        'map_role' => $point['map_role'] ?? null,
                        'readable_location' => $point['readable_location'] ?? null,
                        'barangay_town' => $point['barangay_town'] ?? null,
                        'distance_from_command_center' => $point['distance_from_command_center'] ?? null,
                        'travel_time_from_command_center' => $point['travel_time_from_command_center'] ?? null,
                        'google_maps_url' => $point['google_maps_url'] ?? null,
                        'directions_url' => $point['directions_url'] ?? null,
                        'route_status' => $point['route_status'] ?? null,
                        'route_provider' => $point['route_provider'] ?? null,
                    ])->all())) }}" data-route="{{ e(json_encode($routeData)) }}"></div>
                </div>
            @else
                <div class="data-empty">Coordinates are not available for this report.</div>
            @endif
        </article>

        <article class="panel">
            <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Transmission</p><h2 class="panel-title">Delivery details</h2></div></div>
            <div class="stack">
                <div class="detail-card"><strong>Transmission type</strong><p>{{ strtoupper($report->transmission_type ?: 'online') }}</p></div>
                <div class="detail-card"><strong>Channel</strong><p>{{ $report->channel ?: 'Internet' }}</p></div>
                <div class="detail-card"><strong>Transmitted at</strong><p>{{ optional($report->transmitted_at)->format('M d, Y h:i A') ?? 'Pending' }}</p></div>
                <div class="detail-card"><strong>Status updated</strong><p>{{ optional($report->status_updated_at)->format('M d, Y h:i A') ?? 'Not yet updated' }}</p></div>
            </div>
        </article>
    </section>

    <section class="dual-grid">
        <article class="panel">
            <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Reporter Verification</p><h2 class="panel-title">Realtime selfie capture</h2></div></div>
            <div class="stack">
                @if ($selfieUrl)
                    <div class="detail-card">
                        <strong>Verification selfie</strong>
                        <p>The mobile app required a live front-camera selfie before this report was sent.@if ($report->reporter_selfie_captured_at)<br>Captured: {{ $report->reporter_selfie_captured_at->format('M d, Y h:i A') }}@endif</p>
                        <button type="button" class="media-trigger" data-media-viewer-trigger data-media-type="photo" data-media-src="{{ $selfieUrl }}" data-media-title="{{ $report->report_code }} - {{ $report->reporter_selfie_original_name ?: 'Verification selfie' }}">
                            <img src="{{ $selfieUrl }}" alt="Verification selfie for {{ $report->report_code }}" class="detail-media">
                            <span>Click to enlarge verification selfie</span>
                        </button>
                        <p>{{ $report->reporter_selfie_original_name ?: 'verification-selfie.jpg' }}</p>
                        <div class="action-row"><a href="{{ $selfieUrl }}" class="btn btn-secondary" target="_blank" rel="noopener">Open Selfie</a><a href="{{ $selfieDownloadUrl }}" class="btn btn-primary">Download Selfie</a></div>
                    </div>
                @else
                    <div class="data-empty">No verification selfie is attached to this report.</div>
                @endif
            </div>
        </article>

        <article class="panel">
            <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Evidence</p><h2 class="panel-title">Attached proof</h2></div></div>
            <div class="stack">
                @if ($evidenceUrl && $report->evidence_type === 'photo')
                    <div class="detail-card">
                        <strong>Photo preview</strong>
                        <p>{{ $isCivilian ? 'You can review the uploaded image attached to your report.' : 'Responders can review the uploaded image directly inside the report section.' }}</p>
                        <button type="button" class="media-trigger" data-media-viewer-trigger data-media-type="photo" data-media-src="{{ $evidenceUrl }}" data-media-title="{{ $report->report_code }} - {{ $report->evidence_original_name ?: 'Photo evidence' }}">
                            <img src="{{ $evidenceUrl }}" alt="Photo evidence for {{ $report->report_code }}" class="detail-media">
                            <span>Click to enlarge photo</span>
                        </button>
                    </div>
                @elseif ($evidenceUrl && $report->evidence_type === 'video')
                    <div class="detail-card">
                        <strong>Video preview</strong>
                        <p>{{ $isCivilian ? 'You can replay the uploaded video attached to your report.' : 'Responders can play the uploaded video evidence without leaving the report page.' }}</p>
                        <button type="button" class="media-trigger" data-media-viewer-trigger data-media-type="video" data-media-src="{{ $evidenceUrl }}" data-media-title="{{ $report->report_code }} - {{ $report->evidence_original_name ?: 'Video evidence' }}">
                            <video preload="metadata" playsinline muted class="detail-media"><source src="{{ $evidenceUrl }}">Your browser does not support embedded video playback.</video>
                            <span>Click to enlarge video</span>
                        </button>
                    </div>
                @endif

                <div class="detail-card">
                    <strong>Evidence type</strong>
                    <p>{{ ucfirst($report->evidence_type ?: 'none') }}</p>
                    <p><strong>File:</strong> {{ $report->evidence_original_name ?: 'No file attached' }}</p>
                    @if ($evidenceUrl)
                        <div class="action-row"><a href="{{ $evidenceUrl }}" class="btn btn-secondary" target="_blank" rel="noopener">Open Evidence</a><a href="{{ $downloadUrl }}" class="btn btn-primary">Download File</a></div>
                    @else
                        <p>{{ $report->transmission_type === 'lora' ? 'LoRa fallback reports send compact data only, so no media evidence is attached.' : 'No picture or video evidence was attached to this report.' }}</p>
                    @endif
                </div>
            </div>
        </article>
    </section>

    @unless ($isCivilian)
        <section class="panel">
            <div class="panel-head"><div class="panel-heading"><p class="panel-kicker">Audit Trail</p><h2 class="panel-title">Coordination history</h2><p class="section-copy">Track who assigned, rejected, marked done, or reopened the incident.</p></div></div>
            <div class="timeline">
                @foreach ($auditTrail as $entry)
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-copy">
                            <strong class="timeline-label">{{ $entry['label'] ?? 'Incident updated' }}</strong>
                            <span class="meta">{{ $entry['actor_name'] ?? 'System' }} • {{ $entry['actor_role'] ?? 'Responder' }} • {{ isset($entry['occurred_at']) ? \Carbon\Carbon::parse($entry['occurred_at'])->format('M d, Y h:i A') : 'Timestamp unavailable' }}</span>
                            <p><strong>Status:</strong> {{ ucfirst($entry['status'] ?? $report->status) }}</p>
                            @if (!empty($entry['assigned_responder_name']))
                                <p><strong>Assigned responder:</strong> {{ $entry['assigned_responder_name'] }}</p>
                            @endif
                            @if (!empty($entry['response_notes']))
                                <p><strong>Notes:</strong> {{ $entry['response_notes'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endunless
@endsection
