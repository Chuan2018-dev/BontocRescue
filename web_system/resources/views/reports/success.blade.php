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

@section('hero')
    <section class="hero-card" style="{{ $isCivilian ? 'background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(227,244,255,.94));border-color:rgba(32,104,174,.16);' : '' }}">
        <div class="hero-grid" style="grid-template-columns:minmax(0,1.08fr) minmax(280px,.92fr);">
            <div class="hero-copy">
                <p class="eyebrow">{{ $isCivilian ? 'Submission Success' : 'Report Confirmation' }}</p>
                <h2>{{ $isCivilian ? 'Your emergency report was sent successfully.' : 'Incident submitted successfully.' }}</h2>
                <p>
                    @if ($isCivilian)
                        Report <strong>{{ $report->report_code }}</strong> was accepted by the system. Responders can now review the report, your evidence, your verification selfie, and your location details.
                    @else
                        Report <strong>{{ $report->report_code }}</strong> was accepted by the system and is now visible in the incident feed for responders and dashboard monitoring.
                    @endif
                </p>
                <div class="hero-actions">
                    <a href="{{ route('reports.show', $report) }}" class="btn btn-primary">{{ $isCivilian ? 'Open My Report' : 'Open Incident Details' }}</a>
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
@endsection

@section('content')
    <div hidden data-report-draft-clear-on-success></div>
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
@endsection
