@extends('layouts.app')

@section('title', 'Civilian Accounts')
@section('page_label', 'Responder Access Control')
@section('page_heading', 'Civilian Accounts')
@section('page_subheading', 'Responder-side access support for civilian email recovery and password resets.')

@section('hero')
    <section class="hero-card">
        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Responder-side Access Desk</p>
                <h2>Civilian login recovery without leaving the command center.</h2>
                <p>Use this responder-only panel to review each civilian account, confirm the email they use, and set a new password when they forget their access details.</p>
            </div>
            <div class="hero-metrics">
                <article class="metric-card">
                    <span>Civilian Accounts</span>
                    <strong>{{ $stats['civilian_accounts'] ?? 0 }}</strong>
                    <p>Total civilian profiles currently available for responder-side support.</p>
                </article>
                <article class="metric-card">
                    <span>Updated Today</span>
                    <strong>{{ $stats['updated_today'] ?? 0 }}</strong>
                    <p>Accounts with email or password changes recorded today.</p>
                </article>
                <article class="metric-card">
                    <span>Missing Phone</span>
                    <strong>{{ $stats['missing_phone'] ?? 0 }}</strong>
                    <p>Profiles that may need contact verification before access recovery.</p>
                </article>
                <article class="metric-card">
                    <span>Support Mode</span>
                    <strong>Live</strong>
                    <p>This panel is visible only to responder accounts and hidden from civilians.</p>
                </article>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Civilian Directory</p>
                <h2 class="panel-title">Responder-only account recovery panel</h2>
                <p class="section-copy">Each civilian card shows the stored email address, saved contact number, and a secure field for a new password reset.</p>
            </div>
        </div>

        @if ($civilianAccounts->isEmpty())
            <div class="data-empty">No civilian accounts are available yet.</div>
        @else
            <div class="incident-stack">
                @foreach ($civilianAccounts as $civilianAccount)
                    <article class="incident-card compact-live-card" id="civilian-account-{{ $civilianAccount->id }}">
                        <div class="incident-rail severity-Minor"></div>
                        <div class="incident-meta">
                            <span class="incident-code">Civilian Account</span>
                            <span class="incident-time">{{ $civilianAccount->name }}</span>
                            <span class="tag blue">Civilian</span>
                        </div>
                        <div class="incident-content">
                            <div class="incident-headline">
                                <div>
                                    <h3 class="incident-title">{{ $civilianAccount->email }}</h3>
                                    <p class="incident-summary">Phone: {{ $civilianAccount->responderProfile?->phone ?? 'No contact saved' }}<br>Updated: {{ optional($civilianAccount->updated_at)->format('M d, Y h:i A') ?? 'Not available' }}</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('civilian-accounts.update', $civilianAccount) }}" class="incident-actions">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="civilian_id" value="{{ $civilianAccount->id }}">
                                <div class="form-grid">
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
                                        <label for="contact-{{ $civilianAccount->id }}">Registered Contact</label>
                                        <input
                                            id="contact-{{ $civilianAccount->id }}"
                                            class="input"
                                            type="text"
                                            value="{{ $civilianAccount->responderProfile?->phone ?? 'No contact saved' }}"
                                            readonly
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
                                <p class="section-copy">Leave the password fields blank if you only need to update the civilian email address. Password resets require at least 12 characters.</p>
                                <div class="button-row">
                                    <button type="submit" class="btn btn-primary">Save Civilian Access</button>
                                </div>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
