@extends('layouts.app')

@php
    $isCivilian = auth()->user()?->isCivilian() ?? false;
@endphp

@section('title', $isCivilian ? 'Send Emergency Report' : 'Create Report')
@section('page_label', $isCivilian ? 'Send Report' : 'Manual Report Entry')
@section('page_heading', $isCivilian ? 'Civilian Emergency Report' : 'Create an Incident from the Web Dashboard')
@section('page_subheading', $isCivilian ? 'Use this camera-first civilian form to capture the scene, take a verification selfie, lock GPS, and send the emergency report quickly.' : 'Use this responder-side form for manual incident entry while preserving the standard Laravel incident workflow.')

@section('hero')
    @if ($isCivilian)
        <section class="hero-card" style="background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(225,241,255,.95));border-color:rgba(32,104,174,.16);">
            <div class="hero-grid" style="grid-template-columns:minmax(0,1.12fr) minmax(280px,.88fr);">
                <div class="hero-copy">
                    <p class="eyebrow">Civilian Reporting Flow</p>
                    <h2>Report the emergency first. We will keep the form simple and focused.</h2>
                    <p>
                        This civilian version is different from the responder entry page. It focuses only on the information you need to send quickly:
                        scene capture, verification selfie, location, transport mode, GPS, and the core incident details responders need first.
                    </p>
                    <div class="hero-actions">
                        <a href="#report-form" class="btn btn-primary">Start Reporting</a>
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Open Report History</a>
                        <a href="{{ route('settings.readiness') }}" class="btn btn-secondary">Check Device Readiness</a>
                    </div>
                </div>
                <div class="hero-metrics">
                    <article class="metric-card"><span>Step 1</span><strong>Capture</strong><p>Take a photo or video of the incident first while the scene is still visible.</p></article>
                    <article class="metric-card"><span>Step 2</span><strong>Verify</strong><p>Take a verification selfie so the report has sender identity confirmation.</p></article>
                    <article class="metric-card"><span>Step 3</span><strong>Locate</strong><p>Lock the GPS and add the barangay, road, or landmark if needed.</p></article>
                    <article class="metric-card"><span>Step 4</span><strong>Send</strong><p>Submit online or keep a local draft ready if signal becomes unstable.</p></article>
                </div>
            </div>
        </section>
    @else
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
    @endif
@endsection

@section('content')
    <section id="report-form" class="panel stack" data-report-form-root>
        <div class="panel-head">
            <div class="panel-heading">
                <p class="panel-kicker">{{ $isCivilian ? 'Emergency Form' : 'Incident Form' }}</p>
                <h2 class="panel-title">{{ $isCivilian ? 'Send emergency details' : 'Emergency details' }}</h2>
                <p class="section-copy">{{ $isCivilian ? 'Capture the scene first, then complete the fields below so responders can review your report, location, verification selfie, and attached evidence.' : 'Fill out the operational fields below to submit a new incident from the web dashboard.' }}</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="flash error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="stack" data-report-draft-form data-report-role="{{ $isCivilian ? 'civilian' : 'responder' }}">
            @csrf

            <div class="dual-grid" style="grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);">
                <div class="stack">
                    @if ($isCivilian)
                        <div class="detail-card" style="background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(230,242,255,.96));border-color:rgba(32,104,174,.16);">
                            <strong>Camera-first emergency capture</strong>
                            <p>Start with the required field actions first. Capture a scene photo, take a verification selfie, lock the GPS, then add the short description before the report can be sent.</p>

                            <input type="file" name="evidence_photo_capture" accept="image/*" capture="environment" hidden data-capture-photo-input>
                            <input type="file" name="evidence_video_capture" accept="video/*" capture="environment" hidden data-capture-video-input>
                            <input type="file" name="selfie_capture" accept="image/*" capture="user" hidden data-capture-selfie-input>

                            <div class="settings-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                                <button type="button" class="setting-card capture-action-card" data-capture-trigger="photo">
                                    <strong>Capture Photo</strong>
                                    <p>Required. Use the rear camera for the main still image of the incident scene.</p>
                                    <span class="tag neutral" data-capture-badge="evidence">Evidence pending</span>
                                </button>
                                <button type="button" class="setting-card capture-action-card" data-capture-trigger="video">
                                    <strong>Record Video</strong>
                                    <p>Optional. A video does not replace the required scene photo for submission.</p>
                                    <span class="tag neutral">Optional</span>
                                </button>
                                <button type="button" class="setting-card capture-action-card" data-capture-trigger="selfie">
                                    <strong>Capture Selfie</strong>
                                    <p>Required. Take a front-camera verification selfie before you submit the report.</p>
                                    <span class="tag neutral" data-capture-badge="selfie">Selfie pending</span>
                                </button>
                                <button type="button" class="setting-card capture-action-card" data-capture-trigger="gps">
                                    <strong>Lock GPS</strong>
                                    <p>Required. Ask the browser for the current coordinates and fill the location fields.</p>
                                    <span class="tag neutral" data-capture-badge="gps">GPS pending</span>
                                </button>
                            </div>

                            <div class="detail-card form-readiness-card">
                                <strong>Required before sending</strong>
                                <div class="report-requirements-grid">
                                    <article class="requirement-item">
                                        <strong>Scene photo</strong>
                                        <p>A captured or attached image is required for civilian submission.</p>
                                        <span class="tag red" data-requirement-status="photo">Still required</span>
                                    </article>
                                    <article class="requirement-item">
                                        <strong>Verification selfie</strong>
                                        <p>Responders must be able to verify who sent the report.</p>
                                        <span class="tag red" data-requirement-status="selfie">Still required</span>
                                    </article>
                                    <article class="requirement-item">
                                        <strong>Locked GPS</strong>
                                        <p>Latitude and longitude must be filled before sending.</p>
                                        <span class="tag red" data-requirement-status="gps">Still required</span>
                                    </article>
                                    <article class="requirement-item">
                                        <strong>Short description</strong>
                                        <p>Add the basic incident summary so responders know what to expect.</p>
                                        <span class="tag red" data-requirement-status="description">Still required</span>
                                    </article>
                                </div>
                                <p class="section-copy" data-capture-submit-status>Complete the four required steps first. The Send Emergency Report button will stay locked until all of them are ready.</p>
                            </div>

                            <div class="preview-grid">
                                <article class="preview-card">
                                    <strong>Scene evidence preview</strong>
                                    <img class="preview-thumb" data-evidence-preview-image alt="Evidence preview" hidden>
                                    <video class="preview-thumb" data-evidence-preview-video controls playsinline hidden></video>
                                    <p data-evidence-preview-name>No photo or video selected yet.</p>
                                </article>
                                <article class="preview-card">
                                    <strong>Verification selfie preview</strong>
                                    <img class="preview-thumb" data-selfie-preview-image alt="Selfie preview" hidden>
                                    <p data-selfie-preview-name>No verification selfie selected yet.</p>
                                </article>
                            </div>
                        </div>
                    @endif

                    <div class="form-grid">
                        <div class="field">
                            <label for="incident_type">Incident type</label>
                            <input id="incident_type" class="input" type="text" name="incident_type" value="{{ old('incident_type', $isCivilian ? 'General Emergency' : 'General Emergency') }}" placeholder="Vehicular Accident, Landslide, Fire, Injury" required>
                        </div>
                        <div class="field">
                            <label for="transmission_type">Transmission</label>
                            <select id="transmission_type" name="transmission_type">
                                <option value="online" @selected(old('transmission_type', 'online') === 'online')>Online</option>
                                <option value="lora" @selected(old('transmission_type') === 'lora')>LoRa</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="severity">{{ $isCivilian ? 'Severity preference' : 'Severity override' }}</label>
                            <select id="severity" name="severity">
                                <option value="">AI detect</option>
                                @foreach (['Minor', 'Serious', 'Fatal'] as $severity)
                                    <option value="{{ $severity }}" @selected(old('severity') === $severity)>{{ $severity }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label for="description">Short description</label>
                        <textarea id="description" class="textarea" name="description" required placeholder="Describe the incident, injuries, hazards, road condition, or what responders should expect." data-required-description>{{ old('description') }}</textarea>
                    </div>

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

                    <div class="detail-card">
                        <strong>GPS coordinates</strong>
                        <p>{{ $isCivilian ? 'Tap the button below to use your browser location and automatically fill the Latitude and Longitude fields. Allow browser location permission when prompted.' : 'Use your browser location to quickly populate the GPS coordinates for this manual report. Allow browser location permission when prompted.' }}</p>
                        <div class="button-row">
                            <button type="button" class="btn btn-secondary" data-geo-fill-button>Get Current Latitude and Longitude</button>
                        </div>
                        <p class="section-copy" data-geo-status>Waiting for manual entry or browser GPS request.</p>
                    </div>

                    <div class="detail-card">
                        <strong>Offline draft queue</strong>
                        <p>{{ $isCivilian ? 'If the signal drops while you are encoding, this device can keep your report draft locally until you restore or resend it.' : 'Keep a local backup of the current manual report so command staff do not lose the encoded details during connectivity interruptions.' }}</p>
                        <div class="tag-row">
                            <span class="tag blue" data-draft-queue-count>0 queued</span>
                            <span class="tag neutral" data-draft-autosave-state>Autosave ready</span>
                        </div>
                        <div class="button-row">
                            <button type="button" class="btn btn-secondary" data-draft-save>Save Draft Now</button>
                            <button type="button" class="btn btn-secondary" data-draft-restore>Restore Latest Draft</button>
                            <button type="button" class="btn btn-danger" data-draft-clear>Clear Draft Queue</button>
                        </div>
                        <p class="section-copy" data-draft-status>Current form changes will stay on this device as a local draft, and offline submit will move the report into the queued draft list.</p>
                    </div>
                </div>

                <div class="stack">
                    <div class="detail-card">
                        <strong>{{ $isCivilian ? 'Capture fallback and verification' : 'Evidence upload' }}</strong>
                        <p>{{ $isCivilian ? 'If direct camera capture is not available on this browser, use these fallback file pickers for the scene evidence and the verification selfie.' : 'Attach optional evidence for responder-side manual incident creation.' }}</p>
                        <div class="field">
                            <label for="evidence">{{ $isCivilian ? 'Scene photo file' : 'Photo or video file' }}</label>
                            <input id="evidence" class="input" type="file" name="evidence" accept="{{ $isCivilian ? '.jpg,.jpeg,.png,.webp' : '.jpg,.jpeg,.png,.webp,.mp4,.mov,.avi,.3gp' }}">
                        </div>
                        <div class="field">
                            <label for="selfie">{{ $isCivilian ? 'Verification selfie' : 'Verification selfie (optional)' }}</label>
                            <input id="selfie" class="input" type="file" name="selfie" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        @if ($isCivilian)
                            <p class="section-copy">Civilian web reporting now requires a scene photo, a verification selfie, GPS coordinates, and a short description before the report can be sent.</p>
                        @endif
                    </div>

                    <div class="detail-card">
                        <strong>{{ $isCivilian ? 'Before you send' : 'Dispatch notes' }}</strong>
                        <p>{{ $isCivilian ? 'Online mode sends full details, GPS, scene media, and your verification selfie. LoRa mode is best for fallback situations where internet is weak, but the report should still keep a local draft if signal drops.' : 'Use AI detect for severity when possible, or choose a manual severity value if command review already confirmed the level.' }}</p>
                        <div class="tag-row">
                            <span class="tag blue">Online</span>
                            <span class="tag green">LoRa</span>
                            <span class="tag neutral">GPS Ready</span>
                        </div>
                        <div class="button-row">
                            <a href="{{ route('settings.readiness') }}" class="btn btn-secondary">Open Device Readiness Check</a>
                        </div>
                    </div>

                    <div class="detail-card">
                        <strong>{{ $isCivilian ? 'Submission action' : 'Command action' }}</strong>
                        <p>{{ $isCivilian ? 'The report stays locked until the required scene photo, verification selfie, GPS coordinates, and short description are all ready.' : 'Submit the incident now or return to the main incident feed.' }}</p>
                        <div class="button-row">
                            <button class="btn btn-primary" type="submit" data-draft-submit data-report-submit>{{ $isCivilian ? 'Send Emergency Report' : 'Submit Report' }}</button>
                            <a href="{{ route('reports.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection
