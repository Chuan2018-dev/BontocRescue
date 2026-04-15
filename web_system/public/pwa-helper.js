(function () {
    const dismissedKey = 'bontoc-rescue-pwa-helper-dismissed';
    let deferredPrompt = null;
    let serviceWorkerRegistration = null;
    let refreshingForUpdate = false;

    const standalone = function () {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    };

    const isIOSDevice = function () {
        return /iPad|iPhone|iPod/.test(window.navigator.userAgent) ||
            (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
    };

    const isSafariBrowser = function () {
        return /Safari/i.test(window.navigator.userAgent) &&
            !/CriOS|FxiOS|EdgiOS|OPiOS|Android/i.test(window.navigator.userAgent);
    };

    const supportsServiceWorker = function () {
        return 'serviceWorker' in navigator;
    };

    const ensureStyles = function () {
        if (document.getElementById('bontoc-pwa-helper-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'bontoc-pwa-helper-style';
        style.textContent = `
            .bontoc-pwa-helper {
                position: fixed;
                left: 16px;
                right: 16px;
                bottom: calc(16px + env(safe-area-inset-bottom, 0px));
                z-index: 1600;
                display: grid;
                gap: 12px;
                padding: 16px 18px;
                border-radius: 24px;
                background: rgba(15, 31, 47, 0.95);
                color: #fff;
                box-shadow: 0 24px 60px rgba(12, 25, 39, 0.18);
                backdrop-filter: blur(16px);
            }
            .bontoc-pwa-helper[hidden] { display: none !important; }
            .bontoc-pwa-helper__top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                flex-wrap: wrap;
            }
            .bontoc-pwa-helper__status {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: rgba(255,255,255,0.78);
            }
            .bontoc-pwa-helper__status::before {
                content: "";
                width: 10px;
                height: 10px;
                border-radius: 999px;
                background: #38d39f;
                box-shadow: 0 0 0 6px rgba(56,211,159,.15);
            }
            .bontoc-pwa-helper__status.is-offline::before {
                background: #d18b1f;
                box-shadow: 0 0 0 6px rgba(209,139,31,.15);
            }
            .bontoc-pwa-helper__title {
                margin: 0;
                font-size: 16px;
                font-weight: 800;
                line-height: 1.35;
            }
            .bontoc-pwa-helper__copy {
                margin: 0;
                font-size: 14px;
                line-height: 1.7;
                color: rgba(255,255,255,0.78);
            }
            .bontoc-pwa-helper__actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .bontoc-pwa-helper__btn {
                min-height: 44px;
                border: 1px solid transparent;
                border-radius: 999px;
                padding: 0 16px;
                font-size: 14px;
                font-weight: 800;
                cursor: pointer;
            }
            .bontoc-pwa-helper__btn--primary {
                background: linear-gradient(135deg, #c91c21, #9f1318);
                color: #fff;
            }
            .bontoc-pwa-helper__btn--ghost {
                background: rgba(255,255,255,0.08);
                border-color: rgba(255,255,255,0.16);
                color: #fff;
            }
            @media (max-width: 640px) {
                .bontoc-pwa-helper__actions {
                    flex-direction: column;
                }
                .bontoc-pwa-helper__btn {
                    width: 100%;
                }
            }
        `;

        document.head.appendChild(style);
    };

    const ensureHelper = function () {
        let helper = document.querySelector('[data-bontoc-pwa-helper]');

        if (helper) {
            return helper;
        }

        ensureStyles();
        helper = document.createElement('section');
        helper.hidden = true;
        helper.setAttribute('data-bontoc-pwa-helper', 'true');
        helper.className = 'bontoc-pwa-helper';
        helper.innerHTML = `
            <div class="bontoc-pwa-helper__top">
                <span class="bontoc-pwa-helper__status" data-bontoc-pwa-status>Online Ready</span>
                <strong class="bontoc-pwa-helper__title" data-bontoc-pwa-title>Install Bontoc Rescue</strong>
            </div>
            <p class="bontoc-pwa-helper__copy" data-bontoc-pwa-copy>Use this emergency system like a real app on your phone for quicker launch, full-screen access, and better field reporting.</p>
            <div class="bontoc-pwa-helper__actions">
                <button type="button" class="bontoc-pwa-helper__btn bontoc-pwa-helper__btn--primary" data-bontoc-pwa-install data-install-mode="install">Install App</button>
                <button type="button" class="bontoc-pwa-helper__btn bontoc-pwa-helper__btn--ghost" data-bontoc-pwa-dismiss>Later</button>
            </div>
        `;

        document.body.appendChild(helper);

        helper.querySelector('[data-bontoc-pwa-install]').addEventListener('click', async function () {
            const installMode = this.getAttribute('data-install-mode');

            if (installMode === 'refresh') {
                if (serviceWorkerRegistration && serviceWorkerRegistration.waiting) {
                    refreshingForUpdate = true;
                    serviceWorkerRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
                } else {
                    window.location.reload();
                }
                return;
            }

            if (installMode !== 'install') {
                localStorage.setItem(dismissedKey, '1');
                helper.hidden = true;
                return;
            }

            if (!deferredPrompt) {
                return;
            }

            deferredPrompt.prompt();
            const choice = await deferredPrompt.userChoice;

            if (choice.outcome === 'accepted') {
                localStorage.removeItem(dismissedKey);
            }

            deferredPrompt = null;
            helper.hidden = true;
        });

        helper.querySelector('[data-bontoc-pwa-dismiss]').addEventListener('click', function () {
            localStorage.setItem(dismissedKey, '1');
            helper.hidden = true;
        });

        return helper;
    };

    const updateStatus = function () {
        const status = document.querySelector('[data-bontoc-pwa-status]');

        if (!status) {
            return;
        }

        const online = navigator.onLine;
        status.textContent = online ? 'Online Ready' : 'Offline Mode';
        status.classList.toggle('is-offline', !online);
    };

    const configureHelperCopy = function () {
        const helper = ensureHelper();
        const title = helper.querySelector('[data-bontoc-pwa-title]');
        const copy = helper.querySelector('[data-bontoc-pwa-copy]');
        const install = helper.querySelector('[data-bontoc-pwa-install]');

        if (standalone()) {
            helper.hidden = true;
            return helper;
        }

        if (isIOSDevice() && isSafariBrowser()) {
            title.textContent = 'Install on iPhone or iPad';
            copy.textContent = 'Tap Share in Safari, then choose Add to Home Screen. After that, open Bontoc Rescue from your home screen like a normal app.';
            install.textContent = 'I Understand';
            install.setAttribute('data-install-mode', 'ios-guide');
            return helper;
        }

        if (!supportsServiceWorker()) {
            title.textContent = 'Browser mode is active';
            copy.textContent = 'This device can still use the system in the browser, but install and offline support need a newer Chrome on Android or Safari on iPhone and iPad.';
            install.textContent = 'Use Browser Mode';
            install.setAttribute('data-install-mode', 'browser-only');
            return helper;
        }

        title.textContent = 'Install Bontoc Rescue';
        copy.textContent = 'Use this emergency system like a real app on your phone for quicker launch, full-screen access, and better field reporting.';
        install.textContent = 'Install App';
        install.setAttribute('data-install-mode', 'install');
        return helper;
    };

    const showUpdateHelper = function () {
        const helper = ensureHelper();
        helper.querySelector('[data-bontoc-pwa-title]').textContent = 'New app version available';
        helper.querySelector('[data-bontoc-pwa-copy]').textContent = 'Refresh the app now to load the latest emergency workflow, fixes, and cached assets.';
        helper.querySelector('[data-bontoc-pwa-install]').textContent = 'Refresh App';
        helper.querySelector('[data-bontoc-pwa-install]').setAttribute('data-install-mode', 'refresh');
        updateStatus();
        helper.hidden = false;
    };

    window.addEventListener('load', function () {
        if (supportsServiceWorker()) {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(function (registration) {
                serviceWorkerRegistration = registration;

                if (registration.waiting) {
                    showUpdateHelper();
                }

                registration.addEventListener('updatefound', function () {
                    const newWorker = registration.installing;

                    if (!newWorker) {
                        return;
                    }

                    newWorker.addEventListener('statechange', function () {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            showUpdateHelper();
                        }
                    });
                });
            }).catch(function (error) {
                console.warn('PWA helper service worker registration failed.', error);
            });
        }

        if (!standalone() && localStorage.getItem(dismissedKey) !== '1' && ((isIOSDevice() && isSafariBrowser()) || !supportsServiceWorker())) {
            const helper = configureHelperCopy();
            updateStatus();
            helper.hidden = false;
        }
    });

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;

        if (standalone() || localStorage.getItem(dismissedKey) === '1') {
            return;
        }

        const helper = configureHelperCopy();
        updateStatus();
        helper.hidden = false;
    });

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        localStorage.removeItem(dismissedKey);
        const helper = document.querySelector('[data-bontoc-pwa-helper]');
        if (helper) {
            helper.hidden = true;
        }
    });

    navigator.serviceWorker?.addEventListener('controllerchange', function () {
        if (!refreshingForUpdate) {
            return;
        }

        refreshingForUpdate = false;
        window.location.reload();
    });

    window.addEventListener('online', updateStatus);
    window.addEventListener('offline', updateStatus);
})();
