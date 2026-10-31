import { router } from '@inertiajs/vue3';

/**
 * Makes the web app feel like a native one: the splash fades once the app is ready,
 * a loader shows while the next screen loads, and a banner says when there's no connection.
 */
export function initializeAppFeel(): void {
    hideSplash();
    pageLoader();
    offlineBanner();
}

/** The splash in app.blade.php, shown only in the installed or Android app. */
function hideSplash(): void {
    const splash = document.getElementById('splash');
    if (!splash) {
        return;
    }

    const started =
        (window as Window & { __splashAt?: number }).__splashAt ?? 0;
    // Long enough to read as a brand moment, never a wait.
    const wait = Math.max(0, 600 - (Date.now() - started));

    window.setTimeout(() => {
        splash.classList.add('gone');
        window.setTimeout(() => splash.remove(), 400);
    }, wait);
}

/** A Rasoi spinner over the screen when moving to another page takes a moment. */
function pageLoader(): void {
    const el = document.createElement('div');
    el.className = 'rz-loader';
    el.setAttribute('role', 'status');
    el.innerHTML =
        '<span class="rz-loader-ring" aria-hidden="true"></span><span class="sr-only">Loading</span>';
    document.body.appendChild(el);

    let timer: number | undefined;

    router.on('start', (event) => {
        const visit = event.detail.visit;
        // Sheets and saves update part of the page in place; they have their own feedback.
        if (visit.method !== 'get' || visit.only.length > 0 || visit.prefetch) {
            return;
        }
        timer = window.setTimeout(() => el.classList.add('on'), 180);
    });

    router.on('finish', () => {
        window.clearTimeout(timer);
        el.classList.remove('on');
    });
}

/** Tells people when they're offline, and that their saved week still works. */
function offlineBanner(): void {
    const el = document.createElement('div');
    el.className = 'rz-offline';
    el.setAttribute('role', 'status');
    el.textContent = 'You’re offline. Your saved week still works.';
    document.body.appendChild(el);

    const update = () => el.classList.toggle('on', !navigator.onLine);
    window.addEventListener('online', update);
    window.addEventListener('offline', update);
    update();

    // A visit that couldn't reach the server: show the banner instead of an error.
    router.on('networkError', (event) => {
        el.classList.add('on');
        event.preventDefault();
    });
}
