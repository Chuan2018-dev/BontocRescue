@extends('layouts.app')

@php
    $isCivilian = $user?->isCivilian() ?? false;
    $currentTheme = 'light';
@endphp

@section('title', 'System Settings')
@section('page_label', 'System Settings')
@section('page_heading', 'System Settings')
@section('page_subheading', $isCivilian ? 'Set alerts, fallback mode, and device display from one simple page.' : 'Set alerts, fallback mode, and dashboard display from one simple page.')

@section('hero')
    <section class="hero-card" style="background:rgba(255,255,255,.96);border-color:rgba(15,31,47,.10);">
        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Settings Center</p>
                <h2>Settings that matter.</h2>
                <p>
                    Choose how this account receives alerts, handles weak signal, and displays on this device.
                    Alert and connectivity changes save automatically.
                </p>
                <div class="hero-actions">
                    <a href="{{ $isCivilian ? route('dashboard') : route('monitoring') }}" class="btn btn-primary">{{ $isCivilian ? 'Home' : 'Monitoring' }}</a>
                    <a href="{{ route('profile.show') }}" class="btn btn-secondary">Profile</a>
                </div>
            </div>
            <div class="hero-metrics">
                <article class="metric-card">
                    <span>Critical alerts</span>
                    <strong>{{ $settings['critical_alerts'] ? 'On' : 'Off' }}</strong>
                    <p>Urgent incident warnings.</p>
                </article>
                <article class="metric-card">
                    <span>Push notifications</span>
                    <strong>{{ $settings['push_notifications'] ? 'On' : 'Off' }}</strong>
                    <p>Browser or device alerts.</p>
                </article>
                <article class="metric-card">
                    <span>SMS backup</span>
                    <strong>{{ $settings['sms_backup'] ? 'On' : 'Off' }}</strong>
                    <p>Backup alert path.</p>
                </article>
                <article class="metric-card">
                    <span>Connectivity mode</span>
                    <strong>{{ $settings['connectivity_mode'] === 'lora_fallback' ? 'LoRa' : 'Auto' }}</strong>
                    <p>{{ $settings['connectivity_mode'] === 'lora_fallback' ? 'Prefer fallback when signal drops.' : 'Choose the best available route.' }}</p>
                </article>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="workspace-grid">
        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Notifications</p>
                    <h2 class="panel-title">Alert preferences</h2>
                    <p class="section-copy">Turn alert channels on or off.</p>
                </div>
            </div>

            <div class="toggle-stack" data-settings-root>
                <div class="toggle-item">
                    <div class="toggle-copy">
                        <strong>Critical alerts</strong>
                        <p>For fatal incidents and urgent system notices.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" data-setting-input="critical_alerts" @checked($settings['critical_alerts'])>
                        <span class="switch-track"></span>
                    </label>
                </div>

                <div class="toggle-item">
                    <div class="toggle-copy">
                        <strong>Push notifications</strong>
                        <p>Show updates on this browser or device.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" data-setting-input="push_notifications" @checked($settings['push_notifications'])>
                        <span class="switch-track"></span>
                    </label>
                </div>

                <div class="toggle-item">
                    <div class="toggle-copy">
                        <strong>SMS backup</strong>
                        <p>Use SMS when internet alerts are unreliable.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" data-setting-input="sms_backup" @checked($settings['sms_backup'])>
                        <span class="switch-track"></span>
                    </label>
                </div>
            </div>
        </article>

        <div class="workspace-side">
            <article class="panel">
                <div class="panel-head">
                    <div class="panel-heading">
                        <p class="panel-kicker">Connectivity</p>
                        <h2 class="panel-title">Fallback behavior</h2>
                        <p class="section-copy">Choose automatic routing or LoRa fallback.</p>
                    </div>
                </div>

                <div class="settings-grid">
                    <button type="button" class="setting-card" data-connectivity-mode="auto_select" aria-pressed="{{ $settings['connectivity_mode'] === 'auto_select' ? 'true' : 'false' }}">
                        <strong>Auto Select</strong>
                        <p>Use the best available path.</p>
                        <span class="tag {{ $settings['connectivity_mode'] === 'auto_select' ? 'blue' : 'neutral' }}">Recommended</span>
                    </button>

                    <button type="button" class="setting-card" data-connectivity-mode="lora_fallback" aria-pressed="{{ $settings['connectivity_mode'] === 'lora_fallback' ? 'true' : 'false' }}">
                        <strong>LoRa Fallback</strong>
                        <p>Prefer fallback behavior for weak internet.</p>
                        <span class="tag {{ $settings['connectivity_mode'] === 'lora_fallback' ? 'green' : 'neutral' }}">Fallback Ready</span>
                    </button>
                </div>

                <div class="panel-note">
                    <strong>Current mode</strong>
                    <p class="subtle" data-connectivity-label>{{ $settings['connectivity_mode'] === 'lora_fallback' ? 'LoRa fallback is enabled for this account.' : 'Automatic connectivity selection is enabled for this account.' }}</p>
                </div>
            </article>

            <article class="panel">
                <div class="panel-head">
                    <div class="panel-heading">
                        <p class="panel-kicker">Appearance</p>
                        <h2 class="panel-title">Local display mode</h2>
                        <p class="section-copy">Only affects this browser or device.</p>
                    </div>
                </div>

                <div class="settings-grid">
                    <button type="button" class="setting-card" data-theme-choice="light">
                        <strong>Light Mode</strong>
                        <p>Clear cards and bright display.</p>
                    </button>
                    <button type="button" class="setting-card" data-theme-choice="dark">
                        <strong>Dark Mode</strong>
                        <p>Lower glare for long use.</p>
                    </button>
                </div>
            </article>

            <article class="panel">
                <div class="panel-head">
                    <div class="panel-heading">
                        <p class="panel-kicker">Save Status</p>
                        <h2 class="panel-title">Auto-save summary</h2>
                    </div>
                </div>
                <div class="panel-note">
                    <strong data-settings-status>Ready</strong>
                    <p class="subtle" data-settings-message>Notification and connectivity changes are saved automatically.</p>
                </div>
            </article>
        </div>
    </section>

    <section class="dual-grid">
        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Current Setup</p>
                    <h2 class="panel-title">Preference summary</h2>
                </div>
            </div>
            <div class="meta-list">
                <div class="meta-row"><strong>Critical alerts</strong><span>{{ $settings['critical_alerts'] ? 'Enabled' : 'Disabled' }}</span></div>
                <div class="meta-row"><strong>Push notifications</strong><span>{{ $settings['push_notifications'] ? 'Enabled' : 'Disabled' }}</span></div>
                <div class="meta-row"><strong>SMS backup</strong><span>{{ $settings['sms_backup'] ? 'Enabled' : 'Disabled' }}</span></div>
                <div class="meta-row"><strong>Connectivity mode</strong><span>{{ $settings['connectivity_mode'] === 'lora_fallback' ? 'LoRa fallback' : 'Auto select' }}</span></div>
            </div>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div class="panel-heading">
                    <p class="panel-kicker">Quick Links</p>
                    <h2 class="panel-title">Useful links</h2>
                </div>
            </div>
            <div class="stack">
                <a href="{{ $isCivilian ? route('dashboard') : route('monitoring') }}" class="detail-card">
                    <strong>{{ $isCivilian ? 'Dashboard' : 'Monitoring' }}</strong>
                    <p>{{ $isCivilian ? 'Return to report and status.' : 'Return to live incident monitoring.' }}</p>
                </a>
                <a href="{{ route('reports.index') }}" class="detail-card">
                    <strong>{{ $isCivilian ? 'My Reports' : 'Incident Feed' }}</strong>
                    <p>{{ $isCivilian ? 'Open your report history.' : 'Open the full report queue.' }}</p>
                </a>
                <a href="{{ route('settings.readiness') }}" class="detail-card">
                    <strong>Permission Readiness Check</strong>
                    <p>Check camera, GPS, notifications, and connection.</p>
                </a>
                <a href="{{ route('profile.show') }}" class="detail-card">
                    <strong>{{ $isCivilian ? 'Profile' : 'Responder Profile' }}</strong>
                    <p>Update account details and profile photo.</p>
                </a>
            </div>
        </article>
    </section>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const statusTitle = document.querySelector('[data-settings-status]');
            const statusMessage = document.querySelector('[data-settings-message]');
            const critical = document.querySelector('[data-setting-input="critical_alerts"]');
            const push = document.querySelector('[data-setting-input="push_notifications"]');
            const sms = document.querySelector('[data-setting-input="sms_backup"]');
            const connectivityButtons = Array.from(document.querySelectorAll('[data-connectivity-mode]'));
            const connectivityLabel = document.querySelector('[data-connectivity-label]');
            const themeButtons = Array.from(document.querySelectorAll('[data-theme-choice]'));
            let connectivityMode = @json($settings['connectivity_mode']);

            if (!csrf || !critical || !push || !sms) {
                return;
            }

            const setStatus = (title, message) => {
                if (statusTitle) {
                    statusTitle.textContent = title;
                }

                if (statusMessage) {
                    statusMessage.textContent = message;
                }
            };

            const paintConnectivityButtons = () => {
                connectivityButtons.forEach((button) => {
                    const active = button.dataset.connectivityMode === connectivityMode;
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                    button.style.borderColor = active ? 'rgba(32,104,174,.24)' : 'rgba(15,31,47,.08)';
                    button.style.boxShadow = active ? '0 12px 24px rgba(32,104,174,.10)' : 'none';
                });

                if (connectivityLabel) {
                    connectivityLabel.textContent = connectivityMode === 'lora_fallback'
                        ? 'LoRa fallback is enabled for this account.'
                        : 'Automatic connectivity selection is enabled for this account.';
                }
            };

            const paintThemeButtons = (theme) => {
                themeButtons.forEach((button) => {
                    const active = button.dataset.themeChoice === theme;
                    button.style.borderColor = active ? 'rgba(201,28,33,.24)' : 'rgba(15,31,47,.08)';
                    button.style.boxShadow = active ? '0 12px 24px rgba(201,28,33,.10)' : 'none';
                });
            };

            const saveSettings = async () => {
                setStatus('Saving', 'Updating your settings now...');

                try {
                    const response = await fetch(@json(route('settings.update')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new URLSearchParams({
                            _token: csrf,
                            critical_alerts: critical.checked ? '1' : '0',
                            push_notifications: push.checked ? '1' : '0',
                            sms_backup: sms.checked ? '1' : '0',
                            connectivity_mode: connectivityMode,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('Unable to save settings.');
                    }

                    const data = await response.json();
                    connectivityMode = data.connectivity_mode ?? connectivityMode;
                    paintConnectivityButtons();
                    setStatus('Saved', 'Notification and connectivity changes were saved successfully.');
                } catch (error) {
                    setStatus('Save failed', 'The page could not save your settings. Please try again.');
                }
            };

            [critical, push, sms].forEach((input) => {
                input.addEventListener('change', saveSettings);
            });

            connectivityButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    connectivityMode = button.dataset.connectivityMode || 'auto_select';
                    paintConnectivityButtons();
                    saveSettings();
                });
            });

            themeButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const theme = button.dataset.themeChoice || 'light';
                    localStorage.setItem('stitch-theme', theme);
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                    paintThemeButtons(theme);
                });
            });

            const storedTheme = localStorage.getItem('stitch-theme') || 'light';
            document.documentElement.classList.toggle('dark', storedTheme === 'dark');
            paintThemeButtons(storedTheme);
            paintConnectivityButtons();
        })();
    </script>
@endsection
