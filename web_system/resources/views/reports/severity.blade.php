@extends('layouts.app')

@php
    $isCivilian = auth()->user()?->isCivilian() ?? false;
    $tone = static fn (string $severity): string => match ($severity) {
        'Fatal' => 'red',
        'Serious' => 'amber',
        default => 'green',
    };
    $sourceLabel = static fn (?string $source): string => match ($source) {
        'python_model' => 'Photo AI model',
        'description_fallback' => 'Description fallback',
        'manual_override' => 'Manual override',
        null, '' => 'Source unavailable',
        default => ucwords(str_replace('_', ' ', $source)),
    };
    $statusLabel = static function (array $analysis): string {
        if (($analysis['status'] ?? null) === 'fallback') {
            return 'Fallback mode';
        }

        if (($analysis['status'] ?? null) === 'failed') {
            return 'AI unavailable';
        }

        if ($analysis['review_required']) {
            return 'Responder review needed';
        }

        if (($analysis['status'] ?? null) === 'complete') {
            return 'Ready for triage';
        }

        return 'Pending AI review';
    };
    $statusTone = static function (array $analysis): string {
        if (($analysis['status'] ?? null) === 'failed') {
            return 'red';
        }

        if ($analysis['review_required']) {
            return 'red';
        }

        if (($analysis['status'] ?? null) === 'fallback') {
            return 'amber';
        }

        if (($analysis['status'] ?? null) === 'complete') {
            return 'green';
        }

        return 'neutral';
    };
    $confidenceTone = static function (?string $confidence): string {
        if ($confidence === null || $confidence === '') {
            return 'neutral';
        }

        $value = (float) rtrim($confidence, '%');

        if ($value >= 85) {
            return 'green';
        }

        if ($value >= 60) {
            return 'amber';
        }

        return 'red';
    };
@endphp

@section('title', 'AI Severity Analysis')
@section('page_label', $isCivilian ? 'AI Review' : 'AI Severity Analysis')
@section('page_heading', $isCivilian ? 'Your AI Severity Result' : 'AI Severity Analysis')
@section('page_subheading', $isCivilian ? 'Review the AI-generated severity result for your submitted report in a simpler civilian view.' : 'Review the severity category, confidence score, and dispatch advice generated from the submitted report context.')

@section('hero')
    <section class="hero-card" style="{{ $isCivilian ? 'background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(232,245,255,.95));border-color:rgba(32,104,174,.16);' : '' }}">
        <div class="hero-grid" style="grid-template-columns:minmax(0,1.08fr) minmax(280px,.92fr);">
            <div class="hero-copy">
                <p class="eyebrow">{{ $isCivilian ? 'AI Result' : 'AI Severity Analysis' }}</p>
                <h2>{{ $analysis['predicted'] }} severity classification.</h2>
                <p>
                    @if ($isCivilian)
                        The system reviewed your report <strong>{{ $report->report_code }}</strong> and estimated how serious the emergency may be based on the description you submitted.
                    @else
                        The AI module reviewed report <strong>{{ $report->report_code }}</strong> and produced a severity category, confidence score, and dispatch advice based on the submitted accident description.
                    @endif
                </p>
                <div class="hero-actions">
                    <a href="{{ route('reports.transmissions', $report) }}" class="btn btn-primary">Continue To Transmission</a>
                    <a href="{{ route('reports.show', $report) }}" class="btn btn-secondary">{{ $isCivilian ? 'Back To My Report' : 'Back To Incident Details' }}</a>
                </div>
            </div>
            <div class="hero-metrics">
                <article class="metric-card"><span>Predicted severity</span><strong>{{ $analysis['predicted'] }}</strong><p>{{ $isCivilian ? 'AI estimate for your submitted report.' : 'Current predicted classification from the AI module.' }}</p></article>
                <article class="metric-card"><span>Confidence</span><strong>{{ $analysis['confidence'] }}</strong><p>Estimated confidence score from the AI review.</p></article>
                <article class="metric-card"><span>AI source</span><strong>{{ $sourceLabel($analysis['source']) }}</strong><p>{{ $analysis['model_name'] }}@if ($analysis['model_version']) v{{ $analysis['model_version'] }}@endif</p></article>
                <article class="metric-card"><span>Review state</span><strong>{{ $statusLabel($analysis) }}</strong><p>{{ $analysis['review_required'] ? 'Responder confirmation is recommended before final triage.' : 'This output is ready to support normal responder triage.' }}</p></article>
                <article class="metric-card"><span>Transport</span><strong>{{ strtoupper($report->transmission_type ?: 'online') }}</strong><p>{{ $report->channel ?: 'Internet' }}</p></article>
                <article class="metric-card"><span>Location</span><strong>{{ $report->location_text }}</strong><p>Source location used for the report context.</p></article>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="dual-grid">
        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Prediction</p>
                    <h2 class="panel-title">Severity output</h2>
                    <p class="section-copy">{{ $isCivilian ? 'This tells you how serious the system thinks the incident may be.' : 'Use this output to support responder prioritization and emergency coordination.' }}</p>
                </div>
            </div>
            <div class="stack">
                <div class="detail-card"><strong>Predicted severity</strong><span class="tag {{ $tone($analysis['predicted']) }}">{{ $analysis['predicted'] }}</span></div>
                <div class="detail-card">
                    <strong>AI quick read</strong>
                    <div class="tag-row">
                        <span class="badge {{ $confidenceTone($analysis['confidence']) }}">Confidence {{ $analysis['confidence'] }}</span>
                        <span class="badge blue">{{ $sourceLabel($analysis['source']) }}</span>
                        <span class="badge {{ $statusTone($analysis) }}">{{ $statusLabel($analysis) }}</span>
                    </div>
                    <p>{{ $analysis['advice'] }}</p>
                    <div class="meta-list">
                        <div class="meta-row">
                            <span>AI model</span>
                            <strong>{{ $analysis['model_name'] }}@if ($analysis['model_version']) v{{ $analysis['model_version'] }}@endif</strong>
                        </div>
                        <div class="meta-row">
                            <span>AI status</span>
                            <strong>{{ $statusLabel($analysis) }}</strong>
                        </div>
                        @if ($analysis['processed_at'])
                            <div class="meta-row">
                                <span>Processed at</span>
                                <strong>{{ $analysis['processed_at'] }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
                @if ($analysis['processed_at'])
                    <div class="detail-card"><strong>Prediction timing</strong><p>The AI result was processed at {{ $analysis['processed_at'] }}.</p></div>
                @endif
                @if ($analysis['error_message'])
                    <div class="detail-card"><strong>Fallback note</strong><p>{{ $analysis['error_message'] }}</p></div>
                @endif
            </div>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Source</p>
                    <h2 class="panel-title">Submitted context</h2>
                    <p class="section-copy">Review the exact information used by the AI severity module.</p>
                </div>
            </div>
            <div class="stack">
                <div class="detail-card"><strong>Incident type</strong><p>{{ $report->incident_type }}</p></div>
                <div class="detail-card"><strong>Location</strong><p>{{ $report->location_text }}</p></div>
                <div class="detail-card"><strong>Description</strong><p>{{ $report->description }}</p></div>
            </div>
        </article>
    </section>
@endsection
