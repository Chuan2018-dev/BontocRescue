@extends('layouts.app')

@section('title', 'Civilian Accounts')
@section('page_label', 'Responder Access Control')
@section('page_heading', 'Civilian Accounts')
@section('page_subheading', 'Responder-side account updates, block control, delete access, search tools, and device visibility for civilian users.')

@section('hero')
    <section class="hero-card">
        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Responder-side Access Desk</p>
                <h2>Manage civilian access, suspend risky accounts, and trace responder actions from one clean command panel.</h2>
                <p>
                    This responder-only page now supports full civilian account maintenance: update identity details, reset passwords,
                    block or unblock access, delete old accounts, search the civilian directory quickly, and review the latest audit trail.
                </p>
            </div>
            <div class="hero-metrics">
                <article class="metric-card">
                    <span>Civilian Accounts</span>
                    <strong>{{ $stats['civilian_accounts'] ?? 0 }}</strong>
                    <p>Total civilian profiles currently available for responder-side support.</p>
                </article>
                <article class="metric-card">
                    <span>Filtered View</span>
                    <strong>{{ $stats['filtered_accounts'] ?? 0 }}</strong>
                    <p>Accounts matching the current search and filter options.</p>
                </article>
                <article class="metric-card">
                    <span>Blocked</span>
                    <strong>{{ $stats['blocked_accounts'] ?? 0 }}</strong>
                    <p>Accounts currently suspended from web and API sign-in.</p>
                </article>
                <article class="metric-card">
                    <span>Audit Today</span>
                    <strong>{{ $stats['audit_entries_today'] ?? 0 }}</strong>
                    <p>Responder actions recorded today for civilian account control.</p>
                </article>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Search and Filters</p>
                <h2 class="panel-title">Find the right civilian account faster</h2>
                <p class="section-copy">Search by civilian name, email, contact number, last IP address, or device label. Use filters to isolate blocked accounts, live sessions, or devices quickly.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('civilian-accounts.index') }}" class="incident-actions">
            <div class="form-grid">
                <div class="field">
                    <label for="civilian-search">Search civilian account</label>
                    <input
                        id="civilian-search"
                        class="input"
                        type="text"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Name, email, phone, IP address, or device"
                    >
                </div>
                <div class="field">
                    <label for="civilian-status-filter">Account status</label>
                    <select id="civilian-status-filter" class="input" name="status">
                        <option value="all" @selected($filters['status'] === 'all')>All accounts</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active only</option>
                        <option value="blocked" @selected($filters['status'] === 'blocked')>Blocked only</option>
                        <option value="missing-phone" @selected($filters['status'] === 'missing-phone')>Missing contact number</option>
                        <option value="live-session" @selected($filters['status'] === 'live-session')>With live session</option>
                    </select>
                </div>
                <div class="field">
                    <label for="civilian-device-filter">Device type</label>
                    <select id="civilian-device-filter" class="input" name="device">
                        <option value="all" @selected($filters['device'] === 'all')>All devices</option>
                        <option value="mobile" @selected($filters['device'] === 'mobile')>Mobile devices</option>
                        <option value="desktop" @selected($filters['device'] === 'desktop')>Desktop devices</option>
                        <option value="no-device" @selected($filters['device'] === 'no-device')>No device data yet</option>
                    </select>
                </div>
            </div>
            <div class="button-row">
                <button type="submit" class="btn btn-primary">Apply Search and Filters</button>
                <a href="{{ route('civilian-accounts.index') }}" class="btn btn-secondary">Reset Filters</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Civilian Directory</p>
                <h2 class="panel-title">Responder-only civilian account control panel</h2>
                <p class="section-copy">Each card now shows update fields, block or unblock controls, delete action, and the latest known device and IP activity for the civilian account.</p>
            </div>
        </div>

        @if ($civilianAccounts->isEmpty())
            <div class="data-empty">No civilian accounts matched the current search or filter.</div>
        @else
            <div class="incident-stack">
                @foreach ($civilianAccounts as $civilianAccount)
                    @php
                        $isBlocked = $civilianAccount->isBlocked();
                        $deviceLabel = $civilianAccount->access_device_label ?? 'No device captured yet';
                        $ipAddress = $civilianAccount->access_ip_address ?: 'No IP captured yet';
                        $userAgent = $civilianAccount->access_user_agent ?: 'No browser or device signature captured yet.';
                        $lastSeenHuman = $civilianAccount->access_last_seen_human ?: 'No sign-in activity yet';
                        $lastSeenExact = $civilianAccount->access_last_seen_exact ?: 'Not available';
                        $blockReasonValue = old('block_form_id') == $civilianAccount->id
                            ? old('block_reason', $civilianAccount->blocked_reason)
                            : $civilianAccount->blocked_reason;
                    @endphp
                    <article class="incident-card compact-live-card civilian-account-card" id="civilian-account-{{ $civilianAccount->id }}">
                        <div class="incident-rail {{ $isBlocked ? 'severity-Fatal' : 'severity-Minor' }}"></div>
                        <div class="incident-meta">
                            <span class="incident-code">Civilian Account</span>
                            <span class="incident-time">{{ $civilianAccount->name }}</span>
                            <span class="tag {{ $isBlocked ? 'red' : 'green' }}">{{ $isBlocked ? 'Blocked' : 'Active' }}</span>
                            <span class="tag neutral">{{ $civilianAccount->submitted_reports_count }} Reports</span>
                        </div>
                        <div class="incident-content">
                            <div class="incident-headline">
                                <div>
                                    <h3 class="incident-title">{{ $civilianAccount->email }}</h3>
                                    <p class="incident-summary">
                                        Phone: {{ $civilianAccount->responderProfile?->phone ?? 'No contact saved' }}<br>
                                        Last seen: {{ $lastSeenHuman }}<br>
                                        Updated: {{ optional($civilianAccount->updated_at)->format('M d, Y h:i A') ?? 'Not available' }}
                                    </p>
                                </div>
                                <div class="tag-row">
                                    @if ($civilianAccount->has_live_session)
                                        <span class="tag blue">Live Session</span>
                                    @endif
                                    <span class="tag {{ $isBlocked ? 'red' : 'green' }}">{{ $isBlocked ? 'Login Blocked' : 'Login Allowed' }}</span>
                                </div>
                            </div>

                            <div class="preview-grid civilian-account-summary-grid">
                                <article class="detail-card">
                                    <span class="incident-code">Last Device Used</span>
                                    <strong>{{ $deviceLabel }}</strong>
                                    <p>{{ $userAgent }}</p>
                                </article>
                                <article class="detail-card">
                                    <span class="incident-code">Last IP Address</span>
                                    <strong>{{ $ipAddress }}</strong>
                                    <p>{{ $civilianAccount->has_live_session ? 'Captured from the latest active session.' : 'Captured from the latest successful login.' }}</p>
                                </article>
                                <article class="detail-card">
                                    <span class="incident-code">Last Sign-in</span>
                                    <strong>{{ $lastSeenHuman }}</strong>
                                    <p>{{ $lastSeenExact }}</p>
                                </article>
                                <article class="detail-card">
                                    <span class="incident-code">Block Status</span>
                                    <strong>{{ $isBlocked ? 'Blocked by responder' : 'Access allowed' }}</strong>
                                    <p>
                                        @if ($isBlocked)
                                            {{ $civilianAccount->blocked_reason ?: 'No reason saved.' }}
                                            @if ($civilianAccount->blockedByResponder)
                                                <br>Blocked by: {{ $civilianAccount->blockedByResponder->name }}
                                            @endif
                                        @else
                                            Civilian can log in on web and API as long as credentials are valid.
                                        @endif
                                    </p>
                                </article>
                            </div>

                            <form method="POST" action="{{ route('civilian-accounts.update', $civilianAccount) }}" class="incident-actions">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="civilian_id" value="{{ $civilianAccount->id }}">
                                <div class="form-grid civilian-account-form-grid">
                                    <div class="field">
                                        <label for="name-{{ $civilianAccount->id }}">Civilian Name</label>
                                        <input
                                            id="name-{{ $civilianAccount->id }}"
                                            class="input"
                                            type="text"
                                            name="name"
                                            value="{{ old('civilian_id') == $civilianAccount->id ? old('name', $civilianAccount->name) : $civilianAccount->name }}"
                                            required
                                        >
                                    </div>
                                    <div class="field">
                                        <label for="email-{{ $civilianAccount->id }}">Email / Gmail Used</label>
                                        <input
                                            id="email-{{ $civilianAccount->id }}"
                                            class="input"
                                            type="email"
                                            name="email"
                                            value="{{ old('civilian_id') == $civilianAccount->id ? old('email', $civilianAccount->email) : $civilianAccount->email }}"
                                            required
                                        >
                                    </div>
                                    <div class="field">
                                        <label for="phone-{{ $civilianAccount->id }}">Registered Contact</label>
                                        <input
                                            id="phone-{{ $civilianAccount->id }}"
                                            class="input"
                                            type="text"
                                            name="phone"
                                            value="{{ old('civilian_id') == $civilianAccount->id ? old('phone', $civilianAccount->responderProfile?->phone) : $civilianAccount->responderProfile?->phone }}"
                                            placeholder="Add or update the saved contact number"
                                        >
                                    </div>
                                    <div class="field">
                                        <label for="password-{{ $civilianAccount->id }}">New Password</label>
                                        <input
                                            id="password-{{ $civilianAccount->id }}"
                                            class="input"
                                            type="password"
                                            name="password"
                                            autocomplete="new-password"
                                            placeholder="Leave blank if no password reset is needed"
                                        >
                                    </div>
                                    <div class="field">
                                        <label for="password-confirmation-{{ $civilianAccount->id }}">Confirm New Password</label>
                                        <input
                                            id="password-confirmation-{{ $civilianAccount->id }}"
                                            class="input"
                                            type="password"
                                            name="password_confirmation"
                                            autocomplete="new-password"
                                            placeholder="Repeat the new password"
                                        >
                                    </div>
                                </div>
                                <p class="section-copy">You can update the name, email, and contact number anytime. Password resets now require only a minimum of 6 characters.</p>
                                <div class="button-row">
                                    <button type="submit" class="btn btn-primary">Save Account Update</button>
                                </div>
                            </form>

                            <div class="divider"></div>

                            <div class="preview-grid civilian-account-action-grid">
                                <article class="detail-card">
                                    <span class="incident-code">Suspend or Restore Access</span>
                                    <strong>{{ $isBlocked ? 'Unblock this account' : 'Block this account' }}</strong>
                                    <p>{{ $isBlocked ? 'Restore login access for the civilian on web and API.' : 'Immediately revoke web sessions and API access for this civilian account.' }}</p>
                                    <form method="POST" action="{{ route('civilian-accounts.block', $civilianAccount) }}" class="incident-actions">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="block_form_id" value="{{ $civilianAccount->id }}">
                                        <div class="field">
                                            <label for="block-reason-{{ $civilianAccount->id }}">Block Reason</label>
                                            <input
                                                id="block-reason-{{ $civilianAccount->id }}"
                                                class="input"
                                                type="text"
                                                name="block_reason"
                                                value="{{ $blockReasonValue }}"
                                                placeholder="Add a short reason for blocking this civilian account"
                                            >
                                        </div>
                                        <div class="button-row">
                                            <button type="submit" class="btn {{ $isBlocked ? 'btn-secondary' : 'btn-danger' }}">{{ $isBlocked ? 'Unblock Account' : 'Block Account' }}</button>
                                        </div>
                                    </form>
                                </article>

                                <article class="detail-card">
                                    <span class="incident-code">Delete Access</span>
                                    <strong>Remove this civilian account</strong>
                                    <p>Deleting removes the civilian login and profile record. Existing incident history stays preserved but becomes unlinked from the deleted account.</p>
                                    <form method="POST" action="{{ route('civilian-accounts.destroy', $civilianAccount) }}" onsubmit="return confirm('Delete this civilian account? Existing report history will stay, but the account itself will be removed.')">
                                        @csrf
                                        @method('DELETE')
                                        <div class="button-row">
                                            <button type="submit" class="btn btn-danger">Delete Account</button>
                                        </div>
                                    </form>
                                </article>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Recent Account Activity</p>
                <h2 class="panel-title">Responder audit log</h2>
                <p class="section-copy">Every civilian account update, block, unblock, or delete action is recorded here with the responder name, actor IP, and device label.</p>
            </div>
        </div>

        @if ($auditLogs->isEmpty())
            <div class="data-empty">No account activity matched the current search yet.</div>
        @else
            <div class="timeline">
                @foreach ($auditLogs as $audit)
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <article class="timeline-copy">
                            <div class="tag-row">
                                <span class="tag {{ $audit->action_tone }}">{{ $audit->action_label }}</span>
                                <span class="incident-code">{{ optional($audit->created_at)->format('M d, Y h:i A') }}</span>
                            </div>
                            <strong>{{ $audit->target_name }}{{ $audit->target_email ? ' / '.$audit->target_email : '' }}</strong>
                            <p>
                                Responder: {{ $audit->responder?->name ?? 'Unknown responder' }}<br>
                                Actor IP: {{ $audit->ip_address ?: 'No IP recorded' }}<br>
                                Actor device: {{ $audit->actor_device_label }}<br>
                                {{ $audit->notes }}
                            </p>
                            @if (!empty($audit->context['changed_fields']))
                                <p class="meta">Changed fields: {{ implode(', ', $audit->context['changed_fields']) }}</p>
                            @elseif (array_key_exists('submitted_reports', $audit->context ?? []))
                                <p class="meta">Linked report count at delete time: {{ $audit->context['submitted_reports'] }}</p>
                            @elseif (array_key_exists('reason', $audit->context ?? []) && filled($audit->context['reason']))
                                <p class="meta">Reason: {{ $audit->context['reason'] }}</p>
                            @endif
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
