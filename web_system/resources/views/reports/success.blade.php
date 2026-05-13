@extends('layouts.app')

@php
    $isCivilian = auth()->user()?->isCivilian() ?? false;
    $tone = static fn (string $severity): string => match ($severity) {
        'Fatal' => 'red',
        'Serious' => 'amber',
        default => 'green',
    };
@endphp

@section('title', 'Report Submitted')
@section('page_label', 'Report Confirmation')
@section('page_heading', $isCivilian ? 'Emergency Report Sent' : 'Incident Submitted Successfully')
@section('page_subheading', $isCivilian ? 'Your civilian report was accepted by the system and is now ready for responder review.' : 'The incident was accepted by the system and is now visible for responder monitoring and coordination.')
@section('hide_topbar', $isCivilian ? 'true' : 'false')

@section('hero')
    @if ($isCivilian)
        <section class="hero-card civilian-success-hero">
            <div class="civilian-success-layout">
                <div class="hero-copy">
                    <p class="eyebrow">Emergency Report Sent</p>
                    <h2>Your emergency report was sent successfully.</h2>
                    <p>
                        Reference code: <strong class="incident-code">{{ $report->report_code }}</strong>.
                        Responders can now review your photo, verification selfie, GPS, and short description.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('reports.show', $report) }}" class="btn btn-primary">Open My Report</a>
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Report History</a>
                    </div>
                </div>

                <div class="civilian-success-cards" aria-label="Submitted report summary">
                    <article class="civilian-success-card">
                        <span>Report code</span>
                        <strong>{{ $report->report_code }}</strong>
                    </article>
                    <article class="civilian-success-card">
                        <span>Severity</span>
                        <strong>{{ $report->severity }}</strong>
                    </article>
                    <article class="civilian-success-card">
                        <span>Transmission</span>
                        <strong>{{ strtoupper($report->transmission_type ?: 'online') }}</strong>
                    </article>
                    <article class="civilian-success-card">
                        <span>Location</span>
                        <strong>{{ $report->location_text }}</strong>
                    </article>
                </div>
            </div>
        </section>
    @else
        <section class="hero-card">
            <div class="hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Report Confirmation</p>
                    <h2>Incident submitted successfully.</h2>
                    <p>
                        Report <strong>{{ $report->report_code }}</strong> was accepted by the system and is now visible in the incident feed for responders and dashboard monitoring.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('reports.show', $report) }}" class="btn btn-primary">Open Incident Details</a>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Return To Dashboard</a>
                    </div>
                </div>
                <div class="hero-metrics">
                    <article class="metric-card"><span>Report code</span><strong>{{ $report->report_code }}</strong><p>Reference code for your submitted incident.</p></article>
                    <article class="metric-card"><span>Severity</span><strong>{{ $report->severity }}</strong><p>Current AI-assisted severity classification.</p></article>
                    <article class="metric-card"><span>Transmission</span><strong>{{ strtoupper($report->transmission_type ?: 'online') }}</strong><p>{{ $report->channel ?: 'Internet' }}</p></article>
                    <article class="metric-card"><span>Location</span><strong>{{ $report->location_text }}</strong><p>Submitted location text for this report.</p></article>
                </div>
            </div>
        </section>
    @endif
@endsection

@section('content')
    <div hidden data-report-draft-clear-on-success></div>
    @if ($isCivilian)
        <section class="civilian-success-flow">
            <article class="panel">
                <div class="panel-head">
                    <div class="panel-heading">
                        <p class="panel-kicker">Summary</p>
                        <h2 class="panel-title">What happens next?</h2>
                        <p class="section-copy">Your report is now in the responder queue. Keep this code if responders ask for a reference.</p>
                    </div>
                </div>
                <div class="stack">
                    <div class="detail-card"><strong>Report code</strong><p>{{ $report->report_code }}</p></div>
                    <div class="detail-card"><strong>Current severity</strong><span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span></div>
                    <div class="detail-card"><strong>Location</strong><p>{{ $report->location_text }}</p></div>
                    <div class="detail-card"><strong>AI summary</strong><p>{{ $report->ai_summary ?: 'No AI summary available yet.' }}</p></div>
                </div>
            </article>

            <article class="panel">
                <div class="panel-head">
                    <div class="panel-heading">
                        <p class="panel-kicker">Next Steps</p>
                        <h2 class="panel-title">Track your report</h2>
                        <p class="section-copy">Open your report, check delivery, or return to your report history.</p>
                    </div>
                </div>
                <div class="hero-actions">
                    <a href="{{ route('reports.show', $report) }}" class="btn btn-primary">Open My Report</a>
                    <a href="{{ route('reports.transmissions', $report) }}" class="btn btn-secondary">Transmission Status</a>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">Return to report history</a>
                </div>
            </article>
        </section>
    @else
    <section class="dual-grid">
        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Summary</p>
                    <h2 class="panel-title">Submission result</h2>
                    <p class="section-copy">{{ $isCivilian ? 'Review the main details of the report you just sent.' : 'Review the saved report details before continuing through the responder workflow.' }}</p>
                </div>
            </div>
            <div class="stack">
                <div class="detail-card"><strong>Transmission type</strong><p>{{ strtoupper($report->transmission_type ?: 'online') }}</p></div>
                <div class="detail-card"><strong>Severity</strong><span class="tag {{ $tone($report->severity) }}">{{ $report->severity }}</span></div>
                <div class="detail-card"><strong>Location</strong><p>{{ $report->location_text }}</p></div>
                <div class="detail-card"><strong>AI summary</strong><p>{{ $report->ai_summary ?: 'No AI summary available.' }}</p></div>
            </div>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Next Steps</p>
                    <h2 class="panel-title">Continue workflow</h2>
                    <p class="section-copy">{{ $isCivilian ? 'Continue reviewing your own report flow from the civilian side.' : 'Continue monitoring, AI review, and transmission tracking from the responder workflow.' }}</p>
                </div>
            </div>
            <div class="stack">
                <a href="{{ route('reports.severity', $report) }}" class="detail-card">
                    <strong>Open AI severity analysis</strong>
                    <p>{{ $isCivilian ? 'See how the system evaluated the seriousness of your report.' : 'Review predicted severity and dispatch advice.' }}</p>
                </a>
                <a href="{{ route('reports.transmissions', $report) }}" class="detail-card">
                    <strong>Open transmission status</strong>
                    <p>{{ $isCivilian ? 'Check how your report was delivered through online or LoRa transport.' : 'Check whether the report was delivered through online or LoRa transport.' }}</p>
                </a>
                <a href="{{ route('reports.index') }}" class="detail-card">
                    <strong>{{ $isCivilian ? 'Return to report history' : 'Return to incident feed' }}</strong>
                    <p>{{ $isCivilian ? 'Go back to your civilian report history and see all submitted incidents.' : 'Continue monitoring and coordination from the report queue.' }}</p>
                </a>
            </div>
        </article>
    </section>
    @endif
@endsection
