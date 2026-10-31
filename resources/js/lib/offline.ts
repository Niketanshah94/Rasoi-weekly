import { router } from '@inertiajs/vue3';

/**
 * Registers the service worker (public/sw.js) and keeps its saved pages private:
 * they're filled when a customer page loads and wiped whenever a guest page loads.
 */
export function initializeOffline(): void {
    if (!import.meta.env.PROD || !('serviceWorker' in navigator)) {
        return;
    }

    void navigator.serviceWorker.register('/sw.js').catch(() => undefined);

    const tell = (type: 'warm' | 'clear') =>
        navigator.serviceWorker.controller?.postMessage({ type });
    let warmed = false;

    const onPage = (component: string) => {
        if (component.startsWith('app/')) {
            if (!warmed) {
                warmed = true;
                tell('warm');
            }
        } else if (component.startsWith('auth/') || component === 'Welcome') {
            warmed = false;
            tell('clear');
        }
    };

    router.on('navigate', (event) => onPage(event.detail.page.component));
}
