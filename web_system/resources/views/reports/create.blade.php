@extends('layouts.app')

@php
    $isCivilian = auth()->user()?->isCivilian() ?? false;
    $evidenceUploadError = $errors->first('evidence');
@endphp

@section('title', $isCivilian ? 'Send Emergency Report' : 'Create Report')
@section('page_label', $isCivilian ? 'Send Report' : 'Manual Report Entry')
@section('page_heading', $isCivilian ? 'Civilian Emergency Report' : 'Create an Incident from the Web Dashboard')
@section('page_subheading', $isCivilian ? 'Capture photo, lock GPS, add a short description, then send.' : 'Use this responder-side form for manual incident entry while preserving the standard Laravel incident workflow.')
@section('hide_topbar', $isCivilian ? 'true' : 'false')

@section('hero')
    @unless ($isCivilian)
        <section class="hero-card">
            <div class="hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Manual Report Entry</p>
                    <h2>Create an incident from the web dashboard.</h2>
                    <p>
                        Use this responder-side form to submit an emergency manually. AI severity, transmission mode, GPS coordinates, and optional
                        evidence upload are handled through the same Laravel incident workflow.
                    </p>
                    <div class="hero-actions">
                        <a href="#report-form" class="btn btn-primary">Open Incident Form</a>
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Back To Feed</a>
                        <a href="{{ route('settings.readiness') }}" class="btn btn-secondary">Check Device Readiness</a>
                    </div>
                </div>
                <div class="hero-metrics">
                    <article class="metric-card"><span>Workflow</span><strong>Manual</strong><p>Direct dashboard submission for responder-side entry.</p></article>
                    <article class="metric-card"><span>AI Severity</span><strong>Ready</strong><p>Keep AI detect enabled or override if needed.</p></article>
                    <article class="metric-card"><span>Transport</span><strong>Online / LoRa</strong><p>Choose the delivery channel that matches the scenario.</p></article>
                    <article class="metric-card"><span>Evidence</span><strong>Optional</strong><p>Photo or video uploads remain available in the same form.</p></article>
                </div>
            </div>
        </section>
    @endunless
@endsection

@section('content')
    <section id="report-form" class="panel stack" data-report-form-root>
        <div class="panel-head">
            <div class="panel-heading">
                <p class="panel-kicker">{{ $isCivilian ? 'Emergency Form' : 'Incident Form' }}</p>
                <h2 class="panel-title">{{ $isCivilian ? 'Field actions' : 'Emergency details' }}</h2>
                <p class="section-copy">{{ $isCivilian ? 'Use photo, optional video, and GPS first. Then tap Send Report to submit immediately.' : 'Fill out the operational fields below to submit a new incident from the web dashboard.' }}</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="flash error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="stack" data-report-draft-form data-report-role="{{ $isCivilian ? 'civilian' : 'responder' }}">
            @csrf

            @if ($isCivilian)
                <div class="civilian-report-mobile-flow">
                    <section class="form-step-card civilian-capture-panel">
                        <span class="form-step-label">Emergency Report</span>
                        <strong>Use these field actions.</strong>
                        <p class="civilian-mobile-hint">Photo, GPS, and description are required. Video is optional. Send Report submits immediately.</p>

                        <input type="file" name="evidence_photo_capture" accept="image/*" capture="environment" hidden data-capture-photo-input>
                        <input type="file" name="evidence_video_capture" accept="video/*" capture="environment" hidden data-capture-video-input>
                        <input id="evidence" type="file" name="evidence" accept=".jpg,.jpeg,.png,.webp" hidden>
                        <input type="hidden" name="incident_type" value="{{ old('incident_type', 'General Emergency') }}">
                        <input type="hidden" name="transmission_type" value="{{ old('transmission_type', 'online') }}">
                        <input type="hidden" name="severity" value="{{ old('severity') }}">
                        <input id="location_text" type="hidden" name="location_text" value="{{ old('location_text') }}">
                        <input id="latitude" type="hidden" name="latitude" value="{{ old('latitude') }}" data-geo-latitude>
                        <input id="longitude" type="hidden" name="longitude" value="{{ old('longitude') }}" data-geo-longitude>
                        <button type="button" class="visually-hidden-control" data-geo-fill-button>Get Current Latitude and Longitude</button>

                        <div class="capture-action-grid civilian-three-button-grid">
                            <button type="button" class="setting-card capture-action-card" data-capture-trigger="photo">
                                <span class="capture-action-icon">01</span>
                                <strong>Capture Photo</strong>
                                <p>Scene photo.</p>
                                <span class="tag neutral" data-capture-badge="evidence">Photo pending</span>
                            </button>
                            <button type="button" class="setting-card capture-action-card" data-capture-trigger="video">
                                <span class="capture-action-icon">02</span>
                                <strong>Record Video</strong>
                                <p>Optional proof.</p>
                                <span class="tag neutral" data-capture-badge="video">Optional</span>
                            </button>
                            <button type="button" class="setting-card capture-action-card" data-capture-trigger="gps">
                                <span class="capture-action-icon">03</span>
                                <strong>Lock GPS</strong>
                                <p>Use location.</p>
                                <span class="tag neutral" data-capture-badge="gps">GPS pending</span>
                            </button>
                        </div>

                        <div class="civilian-mobile-status-card">
                            <p data-capture-submit-status>Required first: photo, GPS, and short description.</p>
                            <p data-geo-status>GPS not locked yet.</p>
                            <div class="visually-hidden-control" aria-hidden="true">
                                <span data-requirement-status="photo">Still required</span>
                                <span data-requirement-status="gps">Still required</span>
                                <span data-requirement-status="description">Still required</span>
                                <span data-draft-queue-count>0 queued</span>
                                <span data-draft-autosave-state>Autosave ready</span>
                                <span data-draft-status>Draft ready.</span>
                            </div>
                        </div>

                        <details class="civilian-compact-details">
                            <summary>Preview selected media</summary>
                            <div class="preview-grid">
                                <article class="preview-card">
                                    <strong>Scene evidence preview</strong>
                                    <img class="preview-thumb" data-evidence-preview-image alt="Evidence preview" hidden>
                                    <video class="preview-thumb" data-evidence-preview-video controls playsinline hidden></video>
                                    <p data-evidence-preview-name>No photo or video selected yet.</p>
                                </article>
                            </div>
                        </details>

                        <div class="detail-card inline-warning-card" data-evidence-preview-warning-card hidden data-warning-tone="amber">
                            <div class="tag-row">
                                <span class="tag amber" data-evidence-preview-warning-tag>Preview check</span>
                                <span class="tag neutral">Photo review</span>
                            </div>
                            <strong data-evidence-preview-warning-title>Review the selected evidence photo.</strong>
                            <p data-evidence-preview-warning-body>The selected file may not look like a real accident or emergency scene photo. Replace it now if it seems unrelated.</p>
                            <ul class="section-copy warning-reason-list" data-evidence-preview-warning-list></ul>
                            <div class="button-row warning-actions">
                                <button type="button" class="btn btn-primary" data-evidence-replace-trigger>Replace Photo</button>
                                <button type="button" class="btn btn-secondary" data-warning-dismiss-trigger>Keep Current File</button>
                            </div>
                        </div>
                    </section>

                    <section class="form-step-card civilian-description-panel">
                        <span class="form-step-label">Short Description</span>
                        <strong>Describe the emergency.</strong>
                        <div class="field">
                            <label for="description">Short description</label>
                            <textarea id="description" class="textarea" name="description" required placeholder="Example: Motorcycle crash near the barangay road, one injured person, road partially blocked." data-required-description>{{ old('description') }}</textarea>
                        </div>
                    </section>

                    <section class="form-step-card report-submit-card civilian-send-card">
                        <strong>Send when complete.</strong>
                        <p>Tap Send Report after photo, GPS, and description. No front camera step will open.</p>
                        <button class="btn btn-primary" type="submit" data-draft-submit data-report-submit>Send Report</button>
                    </section>
                </div>
            @else
                <div class="report-form-shell">
                    <div class="report-form-main">
                        <div class="form-step-card">
                            <span class="form-step-label">Step 1 - Describe</span>
                            <strong>Emergency details</strong>
                            <p>Fill out the operational fields below to submit a new incident from the web dashboard.</p>
                            <div class="form-grid">
                                <div class="field">
                                    <label for="incident_type">Incident type</label>
                                    <input id="incident_type" class="input" type="text" name="incident_type" value="{{ old('incident_type', 'General Emergency') }}" placeholder="Vehicular Accident, Landslide, Fire, Injury" required>
                                </div>
                                <div class="field">
                                    <label for="transmission_type">Transmission</label>
                                    <select id="transmission_type" name="transmission_type">
                                        <option value="online" @selected(old('transmission_type', 'online') === 'online')>Online</option>
                                        <option value="lora" @selected(old('transmission_type') === 'lora')>LoRa</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="severity">Severity override</label>
                                    <select id="severity" name="severity">
                                        <option value="">AI detect</option>
                                        @foreach (['Minor', 'Serious', 'Fatal'] as $severity)
                                            <option value="{{ $severity }}" @selected(old('severity') === $severity)>{{ $severity }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="field">
                                <label for="description">Description</label>
                                <textarea id="description" class="textarea" name="description" required placeholder="Describe the incident, injuries, hazards, road condition, or what responders should expect." data-required-description>{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <div class="form-step-card">
                            <span class="form-step-label">Step 2 - Locate</span>
                            <strong>Location and GPS</strong>
                            <p>Use your browser location to quickly populate the GPS coordinates for this manual report.</p>
                            <div class="field">
                                <label for="location_text">Location description</label>
                                <input id="location_text" class="input" type="text" name="location_text" value="{{ old('location_text') }}" placeholder="Barangay, road, landmark, or GPS text" required>
                            </div>
                            <div class="form-grid">
                                <div class="field">
                                    <label for="latitude">Latitude</label>
                                    <input id="latitude" class="input" type="text" name="latitude" value="{{ old('latitude') }}" placeholder="17.089400" data-geo-latitude>
                                </div>
                                <div class="field">
                                    <label for="longitude">Longitude</label>
                                    <input id="longitude" class="input" type="text" name="longitude" value="{{ old('longitude') }}" placeholder="120.977000" data-geo-longitude>
                                </div>
                            </div>
                            <div class="button-row">
                                <button type="button" class="btn btn-secondary" data-geo-fill-button>Get Current Latitude and Longitude</button>
                            </div>
                            <p class="section-copy" data-geo-status>Waiting for manual entry or browser GPS request.</p>
                        </div>

                        <div class="form-step-card">
                            <span class="form-step-label">Step 3 - Save Backup</span>
                            <strong>Offline draft queue</strong>
                            <p>Keep a local backup of the current manual report so command staff do not lose encoded details during connectivity interruptions.</p>
                            <div class="tag-row">
                                <span class="tag blue" data-draft-queue-count>0 queued</span>
                                <span class="tag neutral" data-draft-autosave-state>Autosave ready</span>
                            </div>
                            <div class="button-row">
                                <button type="button" class="btn btn-secondary" data-draft-save>Save Draft Now</button>
                                <button type="button" class="btn btn-secondary" data-draft-restore>Restore Latest Draft</button>
                                <button type="button" class="btn btn-danger" data-draft-clear>Clear Draft Queue</button>
                            </div>
                            <p class="section-copy" data-draft-status>Current form changes will stay on this device as a local draft.</p>
                        </div>
                    </div>

                    <div class="report-form-side">
                        <div class="form-step-card">
                            <strong>Evidence upload</strong>
                            <p>Attach optional evidence for responder-side manual incident creation.</p>
                            <div class="field">
                                <label for="evidence">Photo or video file</label>
                                <input id="evidence" class="input" type="file" name="evidence" accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi,.3gp">
                            </div>
                            <div class="field">
                                <label for="selfie">Verification selfie (optional)</label>
                                <input id="selfie" class="input" type="file" name="selfie" accept=".jpg,.jpeg,.png,.webp">
                            </div>
                        </div>

                        <div class="form-step-card">
                            <strong>Dispatch notes</strong>
                            <p>Use AI detect for severity when possible, or choose a manual severity value if command review already confirmed the level.</p>
                            <div class="tag-row">
                                <span class="tag blue">Online</span>
                                <span class="tag green">LoRa</span>
                                <span class="tag neutral">GPS Ready</span>
                            </div>
                            <div class="button-row">
                                <a href="{{ route('settings.readiness') }}" class="btn btn-secondary">Open Device Readiness Check</a>
                            </div>
                        </div>

                        <div class="form-step-card report-submit-card">
                            <strong>Command action</strong>
                            <p>Submit the incident now or return to the main incident feed.</p>
                            <div class="button-row">
                                <button class="btn btn-primary" type="submit" data-draft-submit data-report-submit>Submit Report</button>
                                <a href="{{ route('reports.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </form>
    </section>
@endsection
