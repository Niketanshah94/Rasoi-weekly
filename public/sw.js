/*
 * Rasoi service worker: keeps this week's plan, shopping list and cook view usable offline (M6).
 * Pages are fetched from the network first and saved; offline, the saved copy is used.
 * Saved pages are deleted when the person logs out or a guest page loads (see resources/js/lib/offline.ts).
 */
const VERSION = 'rasoi-v3';
const PAGES = `${VERSION}-pages`;
const ASSETS = `${VERSION}-assets`;

/** Customer pages kept for offline use. */
const OFFLINE_PATHS = ['/plan', '/groceries', '/cook', '/dishes'];

/** Shown for any page that can't load offline and has no saved copy. */
const OFFLINE_PAGE = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(ASSETS)
            .then((cache) =>
                cache.add(new Request(OFFLINE_PAGE, { cache: 'reload' })),
            )
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((k) => !k.startsWith(VERSION))
                        .map((k) => caches.delete(k)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

function isOfflinePage(url) {
    return (
        url.origin === self.location.origin &&
        OFFLINE_PATHS.includes(url.pathname)
    );
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Build output and fonts have hashed names, so a saved copy never goes stale.
    if (
        url.origin === self.location.origin &&
        (url.pathname.startsWith('/build/') ||
            url.pathname.startsWith('/fonts/'))
    ) {
        event.respondWith(
            caches.match(request).then(
                (hit) =>
                    hit ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches
                                .open(ASSETS)
                                .then((cache) => cache.put(request, copy));
                        }

                        return response;
                    }),
            ),
        );

        return;
    }

    // Full page loads and Inertia visits (Vary: X-Inertia keeps the two apart in the cache).
    if (isOfflinePage(url)) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok && !response.redirected) {
                        const copy = response.clone();
                        caches
                            .open(PAGES)
                            .then((cache) => cache.put(request, copy));
                    }

                    return response;
                })
                .catch(() =>
                    caches
                        .match(request, { ignoreSearch: true })
                        .then(
                            (hit) =>
                                hit ||
                                (request.mode === 'navigate'
                                    ? caches.match(OFFLINE_PAGE)
                                    : undefined),
                        )
                        .then((hit) => hit || Response.error()),
                ),
        );

        return;
    }

    // Any other page opened without a connection gets the friendly offline screen.
    if (request.mode === 'navigate' && url.origin === self.location.origin) {
        event.respondWith(
            fetch(request).catch(() =>
                caches
                    .match(OFFLINE_PAGE)
                    .then((hit) => hit || Response.error()),
            ),
        );
    }
});

self.addEventListener('message', (event) => {
    if (event.origin && event.origin !== self.location.origin) {
        return;
    }

    const data = event.data || {};

    if (data.type === 'clear') {
        event.waitUntil(caches.delete(PAGES));
    }

    // Save the week's pages right after signing in, so they work offline before they're opened,
    // along with the app's code for every screen (the whole build is under 1 MB).
    if (data.type === 'warm') {
        event.waitUntil(
            fetch('/build/manifest.json', { cache: 'no-cache' })
                .then((response) => response.json())
                .then((manifest) => {
                    const files = new Set();
                    Object.values(manifest).forEach((entry) => {
                        files.add(`/build/${entry.file}`);
                        (entry.css || []).forEach((css) =>
                            files.add(`/build/${css}`),
                        );
                    });

                    return caches
                        .open(ASSETS)
                        .then((cache) => cache.addAll([...files]));
                })
                .catch(() => undefined),
        );
        event.waitUntil(
            caches.open(PAGES).then((cache) =>
                Promise.all(
                    OFFLINE_PATHS.map((path) =>
                        fetch(path, { credentials: 'same-origin' })
                            .then((response) =>
                                response.ok && !response.redirected
                                    ? cache.put(path, response)
                                    : undefined,
                            )
                            .catch(() => undefined),
                    ),
                ),
            ),
        );
    }
});
