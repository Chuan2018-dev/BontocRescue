import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const severityColors = {
    Minor: '#1f9d68',
    Serious: '#d18b1f',
    Fatal: '#c72626',
};

const severityTone = {
    Minor: 'green',
    Serious: 'amber',
    Fatal: 'red',
};

const bontocCenter = [10.354270414923162, 124.97039989612004];
const mapStates = [];
let mapDecorationsInjected = false;

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebar();
    initializeMaps();
    initializeNotificationToggle();
    initializeAlertCenter();
    initializeViewToggles();
    initializeSystemClock();
    subscribeToIncidentFeed();
});

function initializeSidebar() {
    const sidebar = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const close = document.querySelector('[data-sidebar-close]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');

    if (!sidebar || !toggle || !backdrop) {
        return;
    }

    const closeSidebar = () => {
        sidebar.classList.remove('is-open');
        backdrop.hidden = true;
        document.body.classList.remove('sidebar-open');
    };

    const openSidebar = () => {
        if (window.innerWidth > 1220) {
            return;
        }

        sidebar.classList.add('is-open');
        backdrop.hidden = false;
        document.body.classList.add('sidebar-open');
    };

    toggle.addEventListener('click', openSidebar);
    close?.addEventListener('click', closeSidebar);
    backdrop.addEventListener('click', closeSidebar);

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 1220) {
                closeSidebar();
            }
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 1220) {
            closeSidebar();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
            closeSidebar();
        }
    });
}

function initializeMaps() {
    ensureMapDecorations();

    document.querySelectorAll('[data-incident-map]').forEach((element) => {
        const points = parseJson(element.dataset.points);
        const route = parseJson(element.dataset.route);
        const map = L.map(element).setView(bontocCenter, 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const state = { element, map, markers: new Map(), points: new Map(), routeLayer: null, route };
        points.forEach((point) => upsertMarker(state, point));
        syncRouteOverlay(state);
        fitMap(state);
        window.setTimeout(() => {
            state.map.invalidateSize();
            fitMap(state);
        }, 180);
        mapStates.push(state);
    });
}

function ensureMapDecorations() {
    if (mapDecorationsInjected) {
        return;
    }

    const style = document.createElement('style');
    style.textContent = `
        .stitch-map-icon{background:transparent;border:0}
        .stitch-map-pin{position:relative;width:34px;height:42px;display:flex;align-items:flex-start;justify-content:center}
        .stitch-map-pin__pulse{position:absolute;top:4px;width:28px;height:28px;border-radius:999px;background:var(--pin-color);opacity:.18;animation:stitch-map-pulse 1.8s ease-out infinite}
        .stitch-map-pin__core{position:relative;z-index:2;width:20px;height:20px;border-radius:999px;background:var(--pin-color);border:3px solid #fff;box-shadow:0 10px 22px rgba(15,31,47,.24)}
        .stitch-map-pin__tip{position:absolute;top:16px;z-index:1;width:14px;height:14px;background:var(--pin-color);transform:rotate(45deg);border-right:3px solid #fff;border-bottom:3px solid #fff;border-radius:2px}
        .stitch-map-pin.is-command-center .stitch-map-pin__core{width:18px;height:18px}
        .stitch-map-pin.is-sender .stitch-map-pin__core{width:22px;height:22px;box-shadow:0 12px 28px rgba(199,38,38,.30)}
        .stitch-map-label,.stitch-map-route-label{background:rgba(15,31,47,.92);border:0;border-radius:999px;color:#fff;font:700 12px/1.2 "Trebuchet MS",sans-serif;letter-spacing:.04em;padding:8px 12px;box-shadow:0 10px 24px rgba(15,31,47,.18)}
        .stitch-map-label-command{background:rgba(32,104,174,.96)}
        .stitch-map-label-sender{background:rgba(199,38,38,.94)}
        .stitch-map-route-label{background:rgba(255,255,255,.96);color:#0f1f2f;border:1px solid rgba(32,104,174,.14);border-radius:18px;padding:10px 12px;box-shadow:0 10px 20px rgba(15,31,47,.12)}
        @keyframes stitch-map-pulse{
            0%{transform:scale(.65);opacity:.70}
            70%{transform:scale(1.5);opacity:0}
            100%{transform:scale(1.5);opacity:0}
        }
    `;

    document.head.append(style);
    mapDecorationsInjected = true;
}

function initializeNotificationToggle() {
    document.querySelectorAll('[data-enable-live-alerts]').forEach((button) => {
        if (!('Notification' in window)) {
            button.disabled = true;
            return;
        }

        updateNotificationButton(button);
        button.addEventListener('click', async () => {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                localStorage.setItem('stitch-live-alerts', 'granted');
            }
            updateNotificationButton(button);
        });
    });
}

function initializeAlertCenter() {
    const panel = document.querySelector('[data-alert-center-panel]');
    const toggle = document.querySelector('[data-alert-center-toggle]');
    const close = document.querySelector('[data-alert-center-close]');

    if (!panel || !toggle) {
        return;
    }

    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        setOpen(panel.hidden);
    });

    close?.addEventListener('click', () => {
        setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            setOpen(false);
        }
    });
}

function initializeViewToggles() {
    document.querySelectorAll('[data-view-toggle-controls]').forEach((controls) => {
        const host = controls.closest('.panel')?.querySelector('[data-view-toggle]');
        if (!host) {
            return;
        }

        const buttons = [...controls.querySelectorAll('[data-view-mode]')];
        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const mode = button.dataset.viewMode ?? 'split';
                host.dataset.viewMode = mode;
                buttons.forEach((item) => item.classList.toggle('is-active', item === button));
                window.setTimeout(() => invalidateVisibleMaps(), 180);
            });
        });
    });
}

function initializeSystemClock() {
    const targets = document.querySelectorAll('[data-system-time]');
    if (targets.length === 0) {
        return;
    }

    const render = () => {
        const now = new Date();
        const formatted = now.toLocaleString('en-PH', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });

        targets.forEach((target) => {
            target.textContent = formatted;
        });
    };

    render();
    window.setInterval(render, 1000);
}

function updateNotificationButton(button) {
    const permission = 'Notification' in window ? Notification.permission : 'denied';

    if (permission === 'granted') {
        button.textContent = 'Live Alerts Enabled';
        button.disabled = true;
        return;
    }

    button.textContent = 'Enable Live Alerts';
}

function subscribeToIncidentFeed() {
    const needsLiveFeed =
        document.querySelector('[data-incident-map]') ||
        document.querySelector('[data-live-incident-list]') ||
        document.querySelector('[data-enable-live-alerts]');

    if (!needsLiveFeed || !window.Echo) {
        return;
    }

    window.Echo.private('responders.incidents').listen('.incident.feed.updated', (payload) => {
        const report = payload.report ?? {};

        renderToast(payload.message ?? 'Incident feed updated.', report);
        maybeShowBrowserNotification(payload.message ?? 'Incident feed updated.', report);
        prependAlert(report);
        updateLiveLists(report, payload.action ?? 'created');
        updateMaps(report);
        updateCounters(report, payload.action ?? 'created');
    });
}

function prependAlert(report) {
    if ((report.severity ?? '') !== 'Fatal') {
        return;
    }

    const list = document.querySelector('[data-alert-list]');
    const counter = document.querySelector('[data-alert-count]');

    if (!list) {
        return;
    }

    list.querySelector('.data-empty')?.remove();

    const item = document.createElement(report.detail_url ? 'a' : 'div');
    if (report.detail_url) {
        item.href = report.detail_url;
    }

    item.className = 'alert-item';
    item.innerHTML = `
        <span class="tag red">Fatal</span>
        <strong>${escapeHtml(report.incident_type ?? 'Critical Incident')}</strong>
        <p>${escapeHtml(report.location_text ?? 'Unknown location')}</p>
        <p>${escapeHtml(report.priority_timer_label ?? 'New alert')}</p>
    `;

    list.prepend(item);
    trimList(list, 6, '.alert-item');

    if (counter) {
        counter.textContent = String(list.querySelectorAll('.alert-item').length);
    }
}

function updateMaps(report) {
    if (report.latitude == null || report.longitude == null) {
        return;
    }

    mapStates.forEach((state) => {
        upsertMarker(state, report);
        syncRouteOverlay(state);
        fitMap(state);
    });
}

function invalidateVisibleMaps() {
    mapStates.forEach((state) => {
        state.map.invalidateSize();
    });
}

function updateLiveLists(report, action) {
    document.querySelectorAll('[data-live-incident-list]').forEach((list) => {
        const existing = list.querySelector(`[data-report-id="${report.id}"]`);
        const card = buildIncidentCard(report);

        if (existing) {
            existing.replaceWith(card);
            return;
        }

        if (action === 'created') {
            list.prepend(card);
            trimList(list, Number.parseInt(list.dataset.maxItems ?? '8', 10), '[data-report-id]');
        }
    });
}

function updateCounters(report, action) {
    if (action !== 'created') {
        return;
    }

    incrementCounter('active');
    incrementCounter('today');

    if (report.severity === 'Fatal') {
        incrementCounter('fatal');
    }
}

function incrementCounter(name) {
    document.querySelectorAll(`[data-live-stat="${name}"]`).forEach((element) => {
        const currentValue = Number.parseInt(element.textContent ?? '0', 10);
        if (Number.isFinite(currentValue)) {
            element.textContent = String(currentValue + 1);
        }
    });
}

function buildIncidentCard(report) {
    const severity = report.severity ?? 'Minor';
    const wrapper = document.createElement(report.detail_url ? 'a' : 'div');
    wrapper.className = 'incident-card compact-live-card';
    wrapper.dataset.reportId = String(report.id ?? '');
    if (report.detail_url) {
        wrapper.href = report.detail_url;
    }

    wrapper.innerHTML = `
        <div class="incident-rail severity-${escapeHtml(severity)}"></div>
        <div class="incident-meta">
            <span class="incident-code">${escapeHtml(report.report_code ?? 'INCIDENT')}</span>
            <strong class="incident-time">${escapeHtml(report.priority_timer_label ?? 'Just received')}</strong>
            <div class="tag-row">
                <span class="tag ${severityTone[severity] ?? 'blue'}">${escapeHtml(severity)}</span>
                <span class="tag blue">${escapeHtml(capitalize(report.status ?? 'received'))}</span>
            </div>
        </div>
        <div class="incident-content">
            <div class="incident-headline">
                <div>
                    <h3 class="incident-title">${escapeHtml(report.incident_type ?? 'New Incident')}</h3>
                    <p class="detail-copy"><strong>${escapeHtml(report.location_text ?? 'Unknown location')}</strong> • ${escapeHtml(report.assigned_responder_name ?? 'Nearest responder pending')}</p>
                </div>
                <div class="tag-row">
                    <span class="tag neutral">${escapeHtml(String(report.transmission_type ?? 'online').toUpperCase())}</span>
                    ${report.evidence_available ? `<span class="tag green">${escapeHtml(capitalize(report.evidence_type ?? 'evidence'))} Attached</span>` : ''}
                    ${report.selfie_available ? '<span class="tag neutral">Selfie Verified</span>' : ''}
                </div>
            </div>
            <p class="incident-summary">${escapeHtml(report.description ?? 'No description provided.')}</p>
        </div>
    `;

    return wrapper;
}

function renderToast(message, report) {
    const stack = document.querySelector('[data-live-toast-stack]');
    if (!stack) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = 'live-toast';
    toast.innerHTML = `<strong>${escapeHtml(report.incident_type ?? 'Live Incident')}</strong><div>${escapeHtml(message)}</div>`;
    stack.prepend(toast);
    window.setTimeout(() => toast.remove(), 5000);
}

function maybeShowBrowserNotification(message, report) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }

    new Notification(report.incident_type ?? 'New Incident', {
        body: message,
        tag: `incident-${report.id ?? Date.now()}`,
    });
}

function upsertMarker(state, point) {
    const identifier = String(point.id ?? '');
    if (!identifier) {
        return;
    }

    const coordinates = [Number(point.latitude), Number(point.longitude)];
    if (!Number.isFinite(coordinates[0]) || !Number.isFinite(coordinates[1])) {
        return;
    }

    state.points.set(identifier, point);

    const popup = createPopupMarkup(point);
    const existing = state.markers.get(identifier);

    if (existing) {
        existing
            .setLatLng(coordinates)
            .setIcon(createIncidentIcon(point))
            .bindPopup(popup)
            .unbindTooltip();

        applyMarkerTooltip(existing, point);
        return;
    }

    const marker = L.marker(coordinates, {
        icon: createIncidentIcon(point),
        zIndexOffset: point.map_role === 'sender' ? 1000 : 0,
    }).bindPopup(popup);

    applyMarkerTooltip(marker, point);

    marker.addTo(state.map);
    state.markers.set(identifier, marker);
}

function applyMarkerTooltip(marker, point) {
    if ((point.map_role ?? '') === 'command_center') {
        marker.bindTooltip('Command Center', {
            permanent: true,
            direction: 'top',
            offset: [0, -28],
            className: 'stitch-map-label stitch-map-label-command',
        });
        return;
    }

    if ((point.map_role ?? '') === 'sender') {
        marker.bindTooltip('Sender Mobile', {
            permanent: false,
            direction: 'top',
            offset: [0, -28],
            className: 'stitch-map-label stitch-map-label-sender',
        });
    }
}

function createIncidentIcon(point) {
    const role = point.map_role ?? 'incident';
    const color = role === 'command_center'
        ? '#2068ae'
        : role === 'sender'
            ? '#c72626'
            : (severityColors[point.severity] ?? '#2068ae');

    const classes = ['stitch-map-pin'];
    if (role === 'command_center') {
        classes.push('is-command-center');
    }
    if (role === 'sender') {
        classes.push('is-sender');
    }

    const pulse = role === 'sender' ? '<span class="stitch-map-pin__pulse"></span>' : '';

    return L.divIcon({
        className: 'stitch-map-icon',
        html: `
            <div class="${classes.join(' ')}" style="--pin-color:${color}">
                ${pulse}
                <span class="stitch-map-pin__core"></span>
                <span class="stitch-map-pin__tip"></span>
            </div>
        `,
        iconSize: [34, 42],
        iconAnchor: [17, 38],
        popupAnchor: [0, -28],
    });
}

function createPopupMarkup(point) {
    const role = point.map_role ?? 'incident';
    const locationLabel = point.readable_location ?? point.location_text ?? 'Unknown location';
    const roleLabel = role === 'command_center'
        ? 'Command Center'
        : role === 'sender'
            ? 'Sender Mobile Location'
            : (point.severity ?? 'Incident');
    const barangayTown = point.barangay_town
        ? `<br><strong>Barangay / town:</strong> ${escapeHtml(point.barangay_town)}`
        : '';
    const distance = point.distance_from_command_center
        ? `<br><strong>Distance from Bontoc Command Center:</strong> ${escapeHtml(point.distance_from_command_center)}`
        : '';
    const travelTime = point.travel_time_from_command_center
        ? `<br><strong>${(point.route_status ?? '') === 'live' ? 'Travel time from Command Center' : 'Estimated travel time'}:</strong> ${escapeHtml(point.travel_time_from_command_center)}`
        : '';
    const mapsLink = point.google_maps_url
        ? `<br><a href="${escapeAttribute(point.google_maps_url)}" target="_blank" rel="noopener">Open in Google Maps</a>`
        : '';
    const directionsLink = point.directions_url && role === 'sender'
        ? `<br><a href="${escapeAttribute(point.directions_url)}" target="_blank" rel="noopener">Open route directions</a>`
        : '';

    return `
        <strong>${escapeHtml(point.incident_type ?? 'Incident')}</strong>
        <br>${escapeHtml(locationLabel)}
        <br>${escapeHtml(roleLabel)}
        ${barangayTown}
        ${distance}
        ${travelTime}
        ${mapsLink}
        ${directionsLink}
    `;
}

function syncRouteOverlay(state) {
    if (state.routeLayer) {
        state.map.removeLayer(state.routeLayer);
        state.routeLayer = null;
    }

    const points = [...state.points.values()];
    const sender = points.find((point) => (point.map_role ?? '') === 'sender');
    const commandCenter = points.find((point) => (point.map_role ?? '') === 'command_center');

    if (!sender || !commandCenter) {
        return;
    }

    const senderCoordinates = [Number(sender.latitude), Number(sender.longitude)];
    const commandCoordinates = [Number(commandCenter.latitude), Number(commandCenter.longitude)];

    if (!Number.isFinite(senderCoordinates[0]) || !Number.isFinite(senderCoordinates[1])) {
        return;
    }

    if (!Number.isFinite(commandCoordinates[0]) || !Number.isFinite(commandCoordinates[1])) {
        return;
    }

    const routeGeometry = Array.isArray(state.route?.geometry)
        ? state.route.geometry
            .map((point) => [Number(point?.lat), Number(point?.lng)])
            .filter((coordinates) => Number.isFinite(coordinates[0]) && Number.isFinite(coordinates[1]))
        : [];
    const hasLiveRoute = (state.route?.status ?? '') === 'live' && routeGeometry.length >= 2;
    const routeSummary = [
        sender.distance_from_command_center ? `Distance: ${sender.distance_from_command_center}` : null,
        sender.travel_time_from_command_center ? `${hasLiveRoute ? 'Travel time' : 'Estimated travel time'}: ${sender.travel_time_from_command_center}` : null,
    ].filter(Boolean).join('<br>');

    state.routeLayer = L.polyline(hasLiveRoute ? routeGeometry : [commandCoordinates, senderCoordinates], {
        color: hasLiveRoute ? '#145da0' : '#2068ae',
        weight: hasLiveRoute ? 5 : 4,
        opacity: hasLiveRoute ? 0.92 : 0.78,
        dashArray: hasLiveRoute ? null : '10 10',
        lineCap: 'round',
        lineJoin: 'round',
    }).bindTooltip(routeSummary || 'Estimated route from Command Center', {
        permanent: true,
        direction: 'center',
        className: 'stitch-map-route-label',
        opacity: 0.95,
    });

    state.routeLayer.addTo(state.map);
}

function fitMap(state) {
    const markers = [...state.markers.values()];
    if (markers.length === 0) {
        state.map.setView(bontocCenter, 14);
        return;
    }

    if (markers.length === 1) {
        state.map.setView(markers[0].getLatLng(), 16);
        return;
    }

    const bounds = L.featureGroup(markers).getBounds();
    state.map.fitBounds(bounds.pad(0.25));
}

function trimList(list, maxItems, selector) {
    if (!Number.isFinite(maxItems)) {
        return;
    }

    list.querySelectorAll(selector).forEach((item, index) => {
        if (index >= maxItems) {
            item.remove();
        }
    });
}

function parseJson(value) {
    try {
        return JSON.parse(value ?? '[]');
    } catch {
        return [];
    }
}

function capitalize(value) {
    if (!value) {
        return '';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function escapeAttribute(value) {
    return escapeHtml(value);
}

