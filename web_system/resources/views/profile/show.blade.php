@extends('layouts.app')

@php
    $isCivilian = $user?->isCivilian() ?? false;
    $profileTitle = $isCivilian ? 'Civilian Profile' : 'Responder Profile';
    $roleLabel = $user?->is_admin ? 'Admin Responder' : ($isCivilian ? 'Civilian' : 'Responder');
    $stationLabel = $profile?->assigned_station ?? ($isCivilian ? 'Civilian Mobile' : 'Field Access');
    $phone = $profile?->phone ?? '';
    $emergencyContactName = $profile?->emergency_contact_name ?? '';
    $emergencyContactPhone = $profile?->emergency_contact_phone ?? '';
    $callsign = $isCivilian ? 'CIV-'.str_pad((string) ($user?->id ?? 1), 3, '0', STR_PAD_LEFT) : 'RES-'.str_pad((string) ($user?->id ?? 1), 3, '0', STR_PAD_LEFT);
    $latestReport = $reportStats['latest'] ?? null;
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)) ?: [])
        ->filter()
        ->map(static fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('');
    $initials = $initials !== '' ? $initials : 'BR';
@endphp

@section('title', $profileTitle)
@section('page_label', $profileTitle)
@section('page_heading', $profileTitle)
@section('page_subheading', $isCivilian ? 'Edit and update your information, manage your profile picture, and review the report activity tied to your civilian account.' : 'Update your responder information, manage your profile picture, and review your current account activity.')

@section('hero')
    <section class="hero-card" style="{{ $isCivilian ? 'background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(232,244,255,.95));border-color:rgba(32,104,174,.16);' : '' }}">
        <div class="hero-grid" style="grid-template-columns:minmax(0,1.08fr) minmax(300px,.92fr);">
            <div class="hero-copy">
                <p class="eyebrow">{{ $isCivilian ? 'Account Identity' : 'Responder Identity' }}</p>
                <h2>{{ $isCivilian ? 'Keep your civilian profile updated and easy to verify.' : 'Keep your responder account details current and deployment-ready.' }}</h2>
                <p>
                    @if ($isCivilian)
                        Your profile helps responders identify your reports, contact information, and verification details quickly. You can edit and update your information here and change your profile picture anytime.
                    @else
                        Use this page to keep your responder information updated, including contact data, profile picture, and quick account details for coordination visibility.
                    @endif
                </p>
                <div class="hero-actions">
                    <a href="#profile-form" class="btn btn-primary">Update your information</a>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">History of Report</a>
                </div>
            </div>
            <div class="hero-metrics">
                <article class="metric-card"><span>Total reports</span><strong>{{ $reportStats['total'] }}</strong><p>{{ $isCivilian ? 'Reports submitted from your civilian account.' : 'Reports linked to your account activity.' }}</p></article>
                <article class="metric-card"><span>Active reports</span><strong>{{ $reportStats['active'] }}</strong><p>{{ $isCivilian ? 'Reports still under active responder handling.' : 'Reports still open or under review.' }}</p></article>
                <article class="metric-card"><span>Completed</span><strong>{{ $reportStats['completed'] }}</strong><p>Reports already marked as completed.</p></article>
                <article class="metric-card"><span>Callsign</span><strong>{{ $callsign }}</strong><p>{{ $stationLabel }}</p></article>
            </div>
        </div>
    </section>
@endsection

@section('content')
    @if ($isCivilian)
        <section class="summary-strip">
            <a href="#profile-form" class="summary-card">
                <span>Update</span>
                <strong>My Details</strong>
                <p>Edit your name, email, and contact information.</p>
            </a>
            <a href="#profile-form" class="summary-card">
                <span>Photo</span>
                <strong>Profile Picture</strong>
                <p>Upload or capture a cleaner profile photo from your phone.</p>
            </a>
            <a href="{{ route('reports.index') }}" class="summary-card">
                <span>Reports</span>
                <strong>History</strong>
                <p>Review your recent reports without leaving the account page.</p>
            </a>
            <a href="{{ route('settings.index') }}" class="summary-card">
                <span>Settings</span>
                <strong>Preferences</strong>
                <p>Jump to notifications, readiness, and local device settings.</p>
            </a>
        </section>
    @endif

    <section class="dual-grid" style="align-items:start;">
        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Profile Snapshot</p>
                    <h2 class="panel-title">Account overview</h2>
                    <p class="section-copy">Review your identity details, profile picture, and linked report summary.</p>
                </div>
            </div>
            <div class="stack">
                <div class="detail-card" style="justify-items:center;text-align:center;background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(232,244,255,.96));border-color:rgba(32,104,174,.16);">
                    @if ($profilePhotoUrl)
                        <img src="{{ $profilePhotoUrl }}" alt="{{ $user->name }} profile picture" style="width:156px;height:156px;border-radius:28px;object-fit:cover;border:1px solid rgba(15,31,47,.08);display:block;">
                    @else
                        <div style="width:156px;height:156px;border-radius:28px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#153c5e,#1f6ca8);color:#fff;font-size:2.6rem;font-weight:900;letter-spacing:.08em;">{{ $initials }}</div>
                    @endif
                    <span class="tag {{ $isCivilian ? 'blue' : ($user?->is_admin ? 'red' : 'green') }}">{{ $roleLabel }}</span>
                    <strong style="font-size:1.25rem;">{{ $user->name }}</strong>
                    <p>{{ $user->email }}</p>
                </div>

                <div class="detail-card">
                    <strong>Contact details</strong>
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p><strong>Phone:</strong> {{ $phone !== '' ? $phone : 'Not yet configured' }}</p>
                    <p><strong>{{ $isCivilian ? 'Community label' : 'Assigned station' }}:</strong> {{ $stationLabel }}</p>
                    <p><strong>Callsign:</strong> {{ $callsign }}</p>
                </div>

                <div class="detail-card">
                    <strong>History of Report</strong>
                    <p>{{ $isCivilian ? 'Open your submitted reports, review statuses, evidence, and transmission updates from a cleaner mobile-friendly history page.' : 'Review report activity linked to your responder account.' }}</p>
                    <div class="action-row">
                        <a href="{{ route('reports.index') }}" class="action-btn secondary">Open Report History</a>
                        <a href="{{ route('reports.create') }}" class="action-btn secondary">{{ $isCivilian ? 'Send New Report' : 'Create Report' }}</a>
                    </div>
                </div>

                <div class="detail-card">
                    <strong>Latest activity</strong>
                    @if ($latestReport)
                        <p><strong>{{ $latestReport->report_code }}</strong> | {{ $latestReport->incident_type }}</p>
                        <div class="tag-row">
                            <span class="tag {{ $latestReport->severity === 'Fatal' ? 'red' : ($latestReport->severity === 'Serious' ? 'amber' : 'green') }}">{{ $latestReport->severity }}</span>
                            <span class="tag blue">{{ ucfirst($latestReport->status) }}</span>
                        </div>
                        <p>{{ $latestReport->location_text }}</p>
                    @else
                        <p>No report activity is linked to this account yet.</p>
                    @endif
                </div>

                <div class="detail-card">
                    <strong>Recent Activity</strong>
                    <p>A quick look at your most recent report activity without leaving the profile page.</p>
                    <div class="stack">
                        @forelse ($recentReports as $report)
                            <a href="{{ route('reports.show', $report) }}" class="detail-card">
                                <strong>{{ $report->report_code }}</strong>
                                <p>{{ $report->incident_type }}</p>
                                <div class="tag-row">
                                    <span class="tag {{ $report->severity === 'Fatal' ? 'red' : ($report->severity === 'Serious' ? 'amber' : 'green') }}">{{ $report->severity }}</span>
                                    <span class="tag blue">{{ ucfirst($report->status) }}</span>
                                </div>
                                <p>{{ $report->location_text }}</p>
                            </a>
                        @empty
                            <div class="data-empty">No report history is available for this account yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </article>

        <article id="profile-form" class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Edit Profile</p>
                    <h2 class="panel-title">Update your information</h2>
                    <p class="section-copy">Change your name, email, contact details, emergency contact, and profile picture from one place.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="stack">
                @csrf
                @method('PUT')

                <div class="settings-grid">
                    <div class="detail-card">
                        <strong>Identity details</strong>
                        <div class="form-grid">
                            <div class="field">
                                <label for="name">Full name</label>
                                <input id="name" class="input" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="field">
                                <label for="email">Email address</label>
                                <input id="email" class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                            </div>
                            <div class="field">
                                <label for="assigned_station">{{ $isCivilian ? 'Community / device label' : 'Assigned station' }}</label>
                                <input id="assigned_station" class="input" type="text" name="assigned_station" value="{{ old('assigned_station', $stationLabel) }}" placeholder="{{ $isCivilian ? 'Civilian Mobile' : 'Bontoc HQ' }}">
                            </div>
                        </div>
                    </div>

                    <div class="detail-card">
                        <strong>Contact and emergency details</strong>
                        <div class="form-grid">
                            <div class="field">
                                <label for="phone">Contact number</label>
                                <input id="phone" class="input" type="text" name="phone" value="{{ old('phone', $phone) }}" placeholder="09171234567">
                            </div>
                            <div class="field">
                                <label for="emergency_contact_name">Emergency contact name</label>
                                <input id="emergency_contact_name" class="input" type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $emergencyContactName) }}" placeholder="Primary contact person">
                            </div>
                            <div class="field">
                                <label for="emergency_contact_phone">Emergency contact number</label>
                                <input id="emergency_contact_phone" class="input" type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $emergencyContactPhone) }}" placeholder="09170000000">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <strong>Change profile picture</strong>
                    <p>Upload a new profile picture to make your account easier to recognize. Accepted formats: JPG, PNG, and WEBP up to 4 MB. New uploads are automatically center-cropped and resized into a clean square before you save.</p>
                    <div class="preview-grid" style="grid-template-columns:minmax(0,180px) minmax(0,1fr);align-items:start;">
                        <div class="detail-card" style="justify-items:center;text-align:center;">
                            <strong>Live photo preview</strong>
                            @if ($profilePhotoUrl)
                                <img
                                    src="{{ $profilePhotoUrl }}"
                                    alt="{{ $user->name }} selected profile preview"
                                    style="width:132px;height:132px;border-radius:24px;object-fit:cover;border:1px solid rgba(15,31,47,.08);display:block;"
                                    data-profile-photo-preview-image
                                >
                                <div data-profile-photo-preview-placeholder hidden style="width:132px;height:132px;border-radius:24px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#153c5e,#1f6ca8);color:#fff;font-size:2rem;font-weight:900;letter-spacing:.08em;">{{ $initials }}</div>
                            @else
                                <img
                                    src=""
                                    alt="{{ $user->name }} selected profile preview"
                                    hidden
                                    style="width:132px;height:132px;border-radius:24px;object-fit:cover;border:1px solid rgba(15,31,47,.08);display:block;"
                                    data-profile-photo-preview-image
                                >
                                <div data-profile-photo-preview-placeholder style="width:132px;height:132px;border-radius:24px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#153c5e,#1f6ca8);color:#fff;font-size:2rem;font-weight:900;letter-spacing:.08em;">{{ $initials }}</div>
                            @endif
                            <span class="incident-code" data-profile-photo-file-name>{{ $profilePhotoUrl ? 'Current profile photo' : 'No photo selected yet' }}</span>
                            <small style="display:block;margin-top:10px;color:#567089;font-size:.82rem;" data-profile-photo-crop-status>{{ $profilePhotoUrl ? 'Current saved photo is shown here.' : 'Auto-crop target: 512 x 512 pixels.' }}</small>
                        </div>
                        <div class="field">
                            <label for="profile_photo">Profile picture</label>
                            <input id="profile_photo" class="input" type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" data-profile-photo-input>
                            <small style="display:block;margin-top:8px;color:#567089;font-size:.82rem;" data-profile-photo-auto-crop>Auto-crop and resize keeps profile photos square and upload-ready.</small>
                            <input type="file" hidden accept="image/*" capture="user" data-profile-photo-camera-input>
                            <input type="hidden" name="remove_profile_photo" value="0">
                            <label style="display:flex;align-items:center;gap:10px;font-size:.92rem;letter-spacing:normal;text-transform:none;">
                                <input type="checkbox" name="remove_profile_photo" value="1" data-profile-photo-remove>
                                Remove current profile picture on save
                            </label>
                            <div class="button-row">
                                <button type="button" class="btn btn-secondary" data-profile-photo-camera-trigger>Capture from camera</button>
                                <button type="button" class="btn btn-secondary" data-profile-photo-reset>Reset preview</button>
                                @if ($profilePhotoUrl)
                                    <button type="button" class="btn btn-danger" data-profile-photo-remove-trigger>Remove current photo</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <strong>Change password</strong>
                    <p>Use this separate section only if you want to update your password. Leave both fields blank if you do not want to change it right now. Minimum password length is 6 characters.</p>
                    <div class="button-row">
                        <button type="button" class="btn btn-secondary" data-password-toggle>Show passwords</button>
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="password">New password</label>
                            <input id="password" class="input" type="password" name="password" autocomplete="new-password" placeholder="Enter new password" data-password-field>
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Confirm new password</label>
                            <input id="password_confirmation" class="input" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Confirm new password" data-password-field>
                        </div>
                        <div class="field">
                            <label>Password reminder</label>
                            <div class="detail-card" style="min-height:50px;">Use a strong password that you can remember and keep private.</div>
                        </div>
                    </div>
                </div>

                <div class="button-row">
                    <button class="btn btn-primary" type="submit">Save Profile Changes</button>
                    <a href="{{ route('settings.index') }}" class="btn btn-secondary">Open Settings</a>
                </div>
            </form>
        </article>
    </section>

    <script>
        (() => {
            const input = document.querySelector('[data-profile-photo-input]');
            const cameraInput = document.querySelector('[data-profile-photo-camera-input]');
            const cameraTrigger = document.querySelector('[data-profile-photo-camera-trigger]');
            const previewImage = document.querySelector('[data-profile-photo-preview-image]');
            const previewPlaceholder = document.querySelector('[data-profile-photo-preview-placeholder]');
            const fileName = document.querySelector('[data-profile-photo-file-name]');
            const cropStatus = document.querySelector('[data-profile-photo-crop-status]');
            const resetButton = document.querySelector('[data-profile-photo-reset]');
            const removeCheckbox = document.querySelector('[data-profile-photo-remove]');
            const removeTrigger = document.querySelector('[data-profile-photo-remove-trigger]');
            const passwordToggle = document.querySelector('[data-password-toggle]');
            const passwordFields = Array.from(document.querySelectorAll('[data-password-field]'));

            const autoCropSize = 512;
            const initialSrc = previewImage?.getAttribute('src') || '';
            const initialHasImage = initialSrc !== '';
            const initialLabel = fileName?.textContent || 'No photo selected yet';
            const initialCropStatus = cropStatus?.textContent || 'Auto-crop target: 512 x 512 pixels.';
            let activeObjectUrl = null;

            const updateCropStatus = (message) => {
                if (cropStatus) {
                    cropStatus.textContent = message;
                }
            };

            const revokeActiveObjectUrl = () => {
                if (activeObjectUrl) {
                    URL.revokeObjectURL(activeObjectUrl);
                    activeObjectUrl = null;
                }
            };

            const renderProfilePhotoState = ({ showInitial = false, message = null } = {}) => {
                if (!previewImage || !previewPlaceholder || !fileName) {
                    return;
                }

                revokeActiveObjectUrl();

                if (showInitial && initialHasImage && !(removeCheckbox?.checked)) {
                    previewImage.src = initialSrc;
                    previewImage.hidden = false;
                    previewPlaceholder.hidden = true;
                    fileName.textContent = initialLabel;
                    updateCropStatus(initialCropStatus);
                    return;
                }

                previewImage.hidden = true;
                previewImage.removeAttribute('src');
                previewPlaceholder.hidden = false;
                fileName.textContent = message ?? 'No photo selected yet';
                updateCropStatus(message ? 'Removal is queued until you save.' : 'Auto-crop target: 512 x 512 pixels.');
            };

            if (!input || !previewImage || !previewPlaceholder || !fileName) {
                return;
            }

            const readImageFile = (file) => new Promise((resolve, reject) => {
                const image = new Image();
                const objectUrl = URL.createObjectURL(file);

                image.onload = () => {
                    URL.revokeObjectURL(objectUrl);
                    resolve(image);
                };

                image.onerror = () => {
                    URL.revokeObjectURL(objectUrl);
                    reject(new Error('Unable to read image file.'));
                };

                image.src = objectUrl;
            });

            const createAutoCroppedFile = async (file) => {
                const image = await readImageFile(file);
                const sourceWidth = image.naturalWidth || image.width;
                const sourceHeight = image.naturalHeight || image.height;
                const sourceSize = Math.min(sourceWidth, sourceHeight);
                const offsetX = Math.max(0, (sourceWidth - sourceSize) / 2);
                const offsetY = Math.max(0, (sourceHeight - sourceSize) / 2);
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');

                if (!context) {
                    throw new Error('Canvas is not available.');
                }

                canvas.width = autoCropSize;
                canvas.height = autoCropSize;
                context.drawImage(image, offsetX, offsetY, sourceSize, sourceSize, 0, 0, autoCropSize, autoCropSize);

                const mimeType = ['image/png', 'image/webp'].includes(file.type) ? file.type : 'image/jpeg';
                const extension = mimeType === 'image/png' ? 'png' : (mimeType === 'image/webp' ? 'webp' : 'jpg');
                const baseName = file.name.replace(/\.(jpe?g|png|webp)$/i, '');

                return new Promise((resolve, reject) => {
                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error('Unable to create cropped image.'));
                            return;
                        }

                        resolve(new File([blob], `${baseName}-square.${extension}`, { type: mimeType }));
                    }, mimeType, 0.92);
                });
            };

            const applyFileToInput = (file) => {
                if (typeof DataTransfer === 'undefined') {
                    return;
                }

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                input.files = dataTransfer.files;
            };

            const preparePhoto = async (file, sourceLabel) => {
                if (!file) {
                    renderProfilePhotoState({ showInitial: true });
                    return;
                }

                if (removeCheckbox) {
                    removeCheckbox.checked = false;
                }

                const croppedFile = await createAutoCroppedFile(file);
                applyFileToInput(croppedFile);
                revokeActiveObjectUrl();
                activeObjectUrl = URL.createObjectURL(croppedFile);
                previewImage.src = activeObjectUrl;
                previewImage.hidden = false;
                previewPlaceholder.hidden = true;
                fileName.textContent = croppedFile.name;
                updateCropStatus(`${sourceLabel} ready: auto-cropped and resized to 512 x 512.`);
            };

            input.addEventListener('change', async () => {
                const [file] = input.files || [];

                if (!file) {
                    renderProfilePhotoState({ showInitial: true });
                    return;
                }

                try {
                    await preparePhoto(file, 'Upload');
                } catch (error) {
                    revokeActiveObjectUrl();
                    activeObjectUrl = URL.createObjectURL(file);
                    previewImage.src = activeObjectUrl;
                    previewImage.hidden = false;
                    previewPlaceholder.hidden = true;
                    fileName.textContent = file.name;
                    updateCropStatus('Preview loaded without auto-crop because the browser could not process the file.');
                }
            });

            cameraTrigger?.addEventListener('click', () => {
                cameraInput?.click();
            });

            cameraInput?.addEventListener('change', async () => {
                const [file] = cameraInput.files || [];

                if (!file) {
                    return;
                }

                try {
                    await preparePhoto(file, 'Camera capture');
                } catch (error) {
                    updateCropStatus('Camera capture is available, but the browser did not return a usable image.');
                }
            });

            resetButton?.addEventListener('click', () => {
                input.value = '';
                if (cameraInput) {
                    cameraInput.value = '';
                }

                if (removeCheckbox?.checked) {
                    renderProfilePhotoState({ message: 'Current photo will be removed after save' });
                    return;
                }

                renderProfilePhotoState({ showInitial: true });
            });

            removeTrigger?.addEventListener('click', () => {
                input.value = '';
                if (cameraInput) {
                    cameraInput.value = '';
                }

                if (removeCheckbox) {
                    removeCheckbox.checked = true;
                }

                renderProfilePhotoState({ message: 'Current photo will be removed after save' });
            });

            removeCheckbox?.addEventListener('change', () => {
                if (removeCheckbox.checked) {
                    input.value = '';
                    if (cameraInput) {
                        cameraInput.value = '';
                    }
                    renderProfilePhotoState({ message: 'Current photo will be removed after save' });
                    return;
                }

                renderProfilePhotoState({ showInitial: true });
            });

            passwordToggle?.addEventListener('click', () => {
                const shouldShow = passwordFields.some((field) => field.type === 'password');

                passwordFields.forEach((field) => {
                    field.type = shouldShow ? 'text' : 'password';
                });

                passwordToggle.textContent = shouldShow ? 'Hide passwords' : 'Show passwords';
            });
        })();
    </script>
@endsection
