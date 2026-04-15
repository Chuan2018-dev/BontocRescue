const CACHE_NAME = 'bontoc-rescue-pwa-v3';
const OFFLINE_URL = '/offline.html';
const APP_SHELL_URLS = [
    '/',
    '/login',
    '/register',
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/icons/icon-64.png',
    '/icons/icon-180.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(APP_SHELL_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        ).then(async () => {
            await self.clients.claim();
            const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });

            clients.forEach((client) => {
                client.postMessage({ type: 'APP_UPDATED_ACTIVE' });
            });
        })
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, responseClone));
                    return response;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(request);

                    if (cachedResponse) {
                        return cachedResponse;
                    }

                    return caches.match(OFFLINE_URL);
                })
        );
        return;
    }

    if (url.origin !== self.location.origin) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            const networkFetch = fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, responseClone));
                    }
                    return response;
                })
                .catch(() => cachedResponse);

            return cachedResponse || networkFetch;
        })
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
