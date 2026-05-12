@extends('layouts.app')

@php
    $isCivilian = $user?->isCivilian() ?? false;
    $currentTheme = 'light';
@endphp

@section('title', 'System Settings')
@section('page_label', 'System Settings')
@section('page_heading', 'System Settings')
@section('page_subheading', $isCivilian ? 'Manage your notification preferences, fallback connectivity behavior, and local appearance settings from one simple page.' : 'Manage notifications, fallback connectivity behavior, and local dashboard appearance without leaving the command workspace.')

@section('hero')
    <section class="hero-card" style="background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(240,246,252,.96));border-color:rgba(32,104,174,.14);">
        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Settings Center</p>
                <h2>Simple controls for alerts, connectivity fallback, and local dashboard preferences.</h2>
                <p>
                    Adjust how this account receives critical updates, how the system behaves during weak connectivity,
                    and how the interface looks on this device. Changes to notifications and connectivity save automatically.
                </p>
                <div class="hero-actions">
                    <a href="{{ $isCivilian ? route('dashboard') : route('monitoring') }}" class="btn btn-primary">{{ $isCivilian ? 'Back To Dashboard' : 'Back To Monitoring' }}</a>
                    <a href="{{ route('profile.show') }}" class="btn btn-secondary">Open Profile</a>
                </div>
            </div>
            <div class="hero-metrics">
                <article class="metric-card">
                    <span>Critical alerts</span>
                    <strong>{{ $settings['critical_alerts'] ? 'On' : 'Off' }}</strong>
                    <p>Highest priority warnings for severe incidents and urgent system notices.</p>
                </article>
                <article class="metric-card">
                    <span>Push notifications</span>
                    <strong>{{ $settings['push_notifications'] ? 'On' : 'Off' }}</strong>
                    <p>Browser or device notifications for regular updates and account activity.</p>
                </article>
                <article class="metric-card">
                    <span>SMS backup</span>
                    <strong>{{ $settings['sms_backup'] ? 'On' : 'Off' }}</strong>
                    <p>Fallback notification path when internet delivery is unreliable.</p>
                </article>
                <article class="metric-card">
                    <span>Connectivity mode</span>
                    <strong>{{ $settings['connectivity_mode'] === 'lora_fallback' ? 'LoRa' : 'Auto' }}</strong>
                    <p>{{ $settings['connectivity_mode'] === 'lora_fallback' ? 'Prefer fallback support when signal quality drops.' : 'Automatically choose the best available route.' }}</p>
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
                    <p class="section-copy">Turn each notification channel on or off depending on how you want to receive updates.</p>
                </div>
            </div>

            <div class="toggle-stack" data-settings-root>
                <div class="toggle-item">
                    <div class="toggle-copy">
                        <strong>Critical alerts</strong>
                        <p>Receive urgent warnings for fatal incidents, immediate dispatch changes, and high-priority system events.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" data-setting-input="critical_alerts" @checked($settings['critical_alerts'])>
                        <span class="switch-track"></span>
                    </label>
                </div>

                <div class="toggle-item">
                    <div class="toggle-copy">
                        <strong>Push notifications</strong>
                        <p>Allow this device or browser to show routine incident updates and activity reminders.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" data-setting-input="push_notifications" @checked($settings['push_notifications'])>
                        <span class="switch-track"></span>
                    </label>
                </div>

                <div class="toggle-item">
                    <div class="toggle-copy">
                        <strong>SMS backup</strong>
                        <p>Use SMS as a backup notification path when normal connectivity becomes unstable.</p>
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
                        <p class="section-copy">Choose whether the account should stay on automatic routing or prefer LoRa fallback mode.</p>
                    </div>
                </div>

                <div class="settings-grid">
                    <button type="button" class="setting-card" data-connectivity-mode="auto_select" aria-pressed="{{ $settings['connectivity_mode'] === 'auto_select' ? 'true' : 'false' }}">
                        <strong>Auto Select</strong>
                        <p>Use the best available path and switch automatically when signal conditions change.</p>
                        <span class="tag {{ $settings['connectivity_mode'] === 'auto_select' ? 'blue' : 'neutral' }}">Recommended</span>
                    </button>

                    <button type="button" class="setting-card" data-connectivity-mode="lora_fallback" aria-pressed="{{ $settings['connectivity_mode'] === 'lora_fallback' ? 'true' : 'false' }}">
                        <strong>LoRa Fallback</strong>
                        <p>Prefer fallback-ready behavior for areas with weak internet or unstable uplink quality.</p>
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
                        <p class="section-copy">This setting only affects the current browser or device.</p>
                    </div>
                </div>

                <div class="settings-grid">
                    <button type="button" class="setting-card" data-theme-choice="light">
                        <strong>Light Mode</strong>
                        <p>Bright command workspace with clear cards and soft contrast.</p>
                    </button>
                    <button type="button" class="setting-card" data-theme-choice="dark">
                        <strong>Dark Mode</strong>
                        <p>Darker interface for lower glare during extended monitoring sessions.</p>
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
                    <h2 class="panel-title">Account preference summary</h2>
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
                    <h2 class="panel-title">Next places you may need</h2>
                </div>
            </div>
            <div class="stack">
                <a href="{{ $isCivilian ? route('dashboard') : route('monitoring') }}" class="detail-card">
                    <strong>{{ $isCivilian ? 'Dashboard' : 'Monitoring' }}</strong>
                    <p>{{ $isCivilian ? 'Return to civilian reporting and history.' : 'Return to live incident monitoring.' }}</p>
                </a>
                <a href="{{ route('reports.index') }}" class="detail-card">
                    <strong>Incident Feed</strong>
                    <p>Open the full report queue, status updates, and evidence review workspace.</p>
                </a>
                <a href="{{ route('settings.readiness') }}" class="detail-card">
                    <strong>Permission Readiness Check</strong>
                    <p>Verify camera, GPS, notifications, and online or offline state on this device before field use.</p>
                </a>
                <a href="{{ route('profile.show') }}" class="detail-card">
                    <strong>Responder Profile</strong>
                    <p>Update your account details, contact info, and profile photo.</p>
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
