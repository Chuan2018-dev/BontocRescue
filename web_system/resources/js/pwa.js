const PWA_DISMISS_KEY = 'bontoc-rescue-pwa-dismissed';
const APP_VERSION_ENDPOINT = '/system/version';
const APP_VERSION_POLL_INTERVAL = 60000;
let deferredInstallPrompt = null;
let serviceWorkerRegistration = null;
let refreshingForUpdate = false;
let activeAppVersion = null;
let appVersionPollHandle = null;
let updateReloadHandle = null;

const isIOSDevice = () =>
    /iPad|iPhone|iPod/.test(window.navigator.userAgent) ||
    (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);

const isSafariBrowser = () =>
    /Safari/i.test(window.navigator.userAgent) &&
    !/CriOS|FxiOS|EdgiOS|OPiOS|Android/i.test(window.navigator.userAgent);

const supportsServiceWorker = () => 'serviceWorker' in navigator;

const inStandaloneMode = () =>
    window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

const updateDisplayMode = () => {
    const displayMode = document.querySelector('[data-pwa-display-mode]');
    const standalone = inStandaloneMode();

    if (!displayMode) {
        document.body?.classList.toggle('is-standalone', standalone);
        document.body?.setAttribute('data-display-mode', standalone ? 'standalone' : 'browser');
        return;
    }

    displayMode.textContent = standalone ? 'Installed' : 'Browser';
    document.body?.classList.toggle('is-standalone', standalone);
    document.body?.setAttribute('data-display-mode', standalone ? 'standalone' : 'browser');
};

const updateNetworkStatus = () => {
    const pill = document.querySelector('[data-pwa-status-pill]');
    const label = document.querySelector('[data-pwa-status-label]');

    if (!pill || !label) {
        return;
    }

    const isOnline = window.navigator.onLine;
    pill.classList.toggle('is-offline', !isOnline);
    label.textContent = isOnline ? 'Online Ready' : 'Offline Mode';
};

const toggleInstallCard = (visible) => {
    const card = document.querySelector('[data-pwa-install-card]');

    if (!card) {
        return;
    }

    card.hidden = !visible;
};

const toggleUpdateCard = (visible) => {
    const card = document.querySelector('[data-pwa-update-card]');

    if (!card) {
        return;
    }

    card.hidden = !visible;
};

const setUpdateCardContent = ({
    eyebrow = 'Updating App',
    title = 'Applying latest system update.',
    copy = 'The website and installed app will reload automatically with the newest emergency workflow and fixes.',
} = {}) => {
    const eyebrowLabel = document.querySelector('[data-pwa-update-eyebrow]');
    const titleLabel = document.querySelector('[data-pwa-update-title]');
    const copyLabel = document.querySelector('[data-pwa-update-copy]');

    if (eyebrowLabel) {
        eyebrowLabel.textContent = eyebrow;
    }

    if (titleLabel) {
        titleLabel.textContent = title;
    }

    if (copyLabel) {
        copyLabel.textContent = copy;
    }
};

const setInstallCardContent = () => {
    const card = document.querySelector('[data-pwa-install-card]');

    if (!card) {
        return;
    }

    const title = card.querySelector('[data-pwa-install-title]');
    const copy = card.querySelector('[data-pwa-install-copy]');
    const primary = card.querySelector('[data-pwa-install-action]');
    const dismiss = card.querySelector('[data-pwa-dismiss-action]');

    if (!title || !copy || !primary || !dismiss) {
        return;
    }

    if (isIOSDevice() && isSafariBrowser() && !inStandaloneMode()) {
        title.textContent = 'Install Bontoc Rescue on iPhone or iPad';
        copy.textContent = 'On iOS Safari, tap Share then choose Add to Home Screen. After that, launch Bontoc Rescue from your home screen like a normal app.';
        primary.textContent = 'I Understand';
        primary.dataset.installMode = 'ios-instructions';
        dismiss.textContent = 'Later';
        return;
    }

    if (!supportsServiceWorker()) {
        title.textContent = 'Browser mode is active';
        copy.textContent = 'This device can still use the system in the browser, but install and offline features need a newer Chrome on Android or Safari on iPhone/iPad.';
        primary.textContent = 'Use Browser Mode';
        primary.dataset.installMode = 'compatibility-info';
        dismiss.textContent = 'Later';
        return;
    }

    title.textContent = 'Open the emergency system like a mobile app.';
    copy.textContent = 'Install this web app on your phone or desktop for a full-screen experience, faster launch, and offline fallback screen during weak connectivity.';
    primary.textContent = 'Install App';
    primary.dataset.installMode = 'install';
    dismiss.textContent = 'Later';
};

const shouldShowInstallCard = () => {
    if (inStandaloneMode()) {
        return false;
    }

    return window.localStorage.getItem(PWA_DISMISS_KEY) !== '1';
};

const clearPendingReload = () => {
    if (updateReloadHandle) {
        window.clearTimeout(updateReloadHandle);
        updateReloadHandle = null;
    }
};

const scheduleReloadForUpdate = (delay = 1200) => {
    clearPendingReload();

    updateReloadHandle = window.setTimeout(() => {
        window.location.reload();
    }, delay);
};

const activateWaitingWorker = (worker = null) => {
    const waitingWorker = worker ?? serviceWorkerRegistration?.waiting;

    if (!waitingWorker) {
        return false;
    }

    waitingWorker.postMessage({ type: 'SKIP_WAITING' });
    return true;
};

const beginAutomaticUpdate = (worker = null) => {
    setUpdateCardContent();
    toggleUpdateCard(true);
    refreshingForUpdate = true;

    const activatedWaitingWorker = activateWaitingWorker(worker);
    scheduleReloadForUpdate(activatedWaitingWorker ? 3500 : 1200);
};

const fetchSystemVersion = async () => {
    try {
        const response = await fetch(APP_VERSION_ENDPOINT, {
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return null;
        }

        const payload = await response.json();
        return typeof payload?.version === 'string' ? payload.version : null;
    } catch (error) {
        console.warn('System version check failed.', error);
        return null;
    }
};

const requestServiceWorkerUpdate = async () => {
    if (!serviceWorkerRegistration) {
        return;
    }

    try {
        await serviceWorkerRegistration.update();
    } catch (error) {
        console.warn('Service worker update check failed.', error);
    }
};

const captureCurrentAppVersion = async () => {
    activeAppVersion = await fetchSystemVersion();
};

const checkForApplicationUpdates = async () => {
    if (refreshingForUpdate) {
        return;
    }

    await requestServiceWorkerUpdate();

    if (serviceWorkerRegistration?.waiting) {
        beginAutomaticUpdate();
        return;
    }

    const latestVersion = await fetchSystemVersion();

    if (!latestVersion) {
        return;
    }

    if (!activeAppVersion) {
        activeAppVersion = latestVersion;
        return;
    }

    if (latestVersion !== activeAppVersion) {
        beginAutomaticUpdate();
    }
};

const scheduleVersionPolling = () => {
    if (appVersionPollHandle) {
        return;
    }

    appVersionPollHandle = window.setInterval(() => {
        void checkForApplicationUpdates();
    }, APP_VERSION_POLL_INTERVAL);
};

const registerServiceWorker = async () => {
    if (!supportsServiceWorker()) {
        return;
    }

    try {
        serviceWorkerRegistration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

        if (serviceWorkerRegistration.waiting) {
            beginAutomaticUpdate();
        }

        serviceWorkerRegistration.addEventListener('updatefound', () => {
            const newWorker = serviceWorkerRegistration?.installing;

            if (!newWorker) {
                return;
            }

            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    beginAutomaticUpdate(newWorker);
                }
            });
        });
    } catch (error) {
        console.warn('PWA service worker registration failed.', error);
    }
};

const installApp = async () => {
    const installButton = document.querySelector('[data-pwa-install-action]');
    const installMode = installButton?.dataset.installMode ?? 'install';

    if (installMode !== 'install') {
        window.localStorage.setItem(PWA_DISMISS_KEY, '1');
        toggleInstallCard(false);
        return;
    }

    if (!deferredInstallPrompt) {
        return;
    }

    deferredInstallPrompt.prompt();
    const { outcome } = await deferredInstallPrompt.userChoice;

    if (outcome === 'accepted') {
        window.localStorage.removeItem(PWA_DISMISS_KEY);
    }

    deferredInstallPrompt = null;
    toggleInstallCard(false);
    updateDisplayMode();
};

document.addEventListener('DOMContentLoaded', () => {
    updateDisplayMode();
    updateNetworkStatus();
    setInstallCardContent();
    setUpdateCardContent();
    void registerServiceWorker();
    void captureCurrentAppVersion();
    scheduleVersionPolling();
    window.setTimeout(() => {
        void checkForApplicationUpdates();
    }, 5000);

    const installButton = document.querySelector('[data-pwa-install-action]');
    const dismissButton = document.querySelector('[data-pwa-dismiss-action]');

    installButton?.addEventListener('click', () => {
        void installApp();
    });

    dismissButton?.addEventListener('click', () => {
        window.localStorage.setItem(PWA_DISMISS_KEY, '1');
        toggleInstallCard(false);
    });

    if ((isIOSDevice() && isSafariBrowser() && shouldShowInstallCard()) || (!supportsServiceWorker() && shouldShowInstallCard())) {
        toggleInstallCard(true);
    }
});

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    setInstallCardContent();
    toggleInstallCard(shouldShowInstallCard());
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    toggleInstallCard(false);
    updateDisplayMode();
});

navigator.serviceWorker?.addEventListener('controllerchange', () => {
    if (!refreshingForUpdate) {
        return;
    }

    clearPendingReload();
    refreshingForUpdate = false;
    window.location.reload();
});

navigator.serviceWorker?.addEventListener('message', (event) => {
    if (event.data?.type === 'APP_UPDATED_ACTIVE' && !refreshingForUpdate) {
        toggleUpdateCard(false);
    }
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        void checkForApplicationUpdates();
    }
});

window.addEventListener('focus', () => {
    void checkForApplicationUpdates();
});

window.addEventListener('online', () => {
    updateNetworkStatus();
    void checkForApplicationUpdates();
});

window.addEventListener('offline', () => {
    updateNetworkStatus();
});
