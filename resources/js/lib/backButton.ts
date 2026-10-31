import { router } from '@inertiajs/vue3';

/**
 * Android's back button, the way native apps behave: close what's open, step back a level,
 * go home from other tabs, and only close the app on a second press from the home screen.
 *
 * Screens can claim a press first: they listen for "rasoi:back" on window and call
 * preventDefault() when they handle it (an open sheet closes, onboarding steps back).
 */

type BackButtonPlugin = {
    addListener: (event: 'backButton', handler: () => void) => unknown;
    exitApp: () => void;
};

type CapacitorWindow = Window & {
    Capacitor?: { Plugins?: { App?: BackButtonPlugin } };
};

const BACK_EVENT = 'rasoi:back';

/** Ask the open screen to handle back itself. True if it did. */
export function claimedByScreen(): boolean {
    const event = new CustomEvent(BACK_EVENT, { cancelable: true });
    window.dispatchEvent(event);

    return event.defaultPrevented;
}

/** Where back goes from each screen: a path, "history" for the previous page, or null for a home screen. */
export function backTarget(component: string): string | null {
    switch (true) {
        case [
            'app/Dishes',
            'app/Groceries',
            'app/Cook',
            'app/Account',
        ].includes(component):
            return '/plan';
        case component.startsWith('settings/'):
            return '/account';
        case component === 'auth/CheckInbox':
            return '/login';
        case component.startsWith('auth/'):
            return '/';
        case component.startsWith('legal/'):
            return 'history';
        default:
            // Plan, the landing page and onboarding's first step.
            return null;
    }
}

export function initializeBackButton(): void {
    const app = (window as CapacitorWindow).Capacitor?.Plugins?.App;
    if (!app) {
        return;
    }

    // "navigate" covers page loads and history moves; "success" also covers the replace
    // visits this handler makes, which don't fire "navigate".
    let component = '';
    router.on('navigate', (event) => {
        component = event.detail.page.component;
    });
    router.on('success', (event) => {
        component = event.detail.page.component;
    });

    const toast = document.createElement('div');
    toast.className = 'rz-exit-toast';
    toast.setAttribute('role', 'status');
    toast.textContent = 'Press back again to close Rasoi';
    document.body.appendChild(toast);
    let armedUntil = 0;

    app.addListener('backButton', () => {
        if (claimedByScreen()) {
            return;
        }

        const target = backTarget(component);

        if (target === 'history') {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                router.visit('/');
            }

            return;
        }

        if (target) {
            router.visit(target, { replace: true });

            return;
        }

        // Home screen: a second press within two seconds closes the app.
        if (Date.now() < armedUntil) {
            app.exitApp();

            return;
        }
        armedUntil = Date.now() + 2000;
        toast.classList.add('on');
        window.setTimeout(() => toast.classList.remove('on'), 2000);
    });
}

/** For screens: run `handler` when back is pressed; return true from it to claim the press. */
export function onBack(handler: () => boolean): () => void {
    const listener = (event: Event) => {
        if (!event.defaultPrevented && handler()) {
            event.preventDefault();
        }
    };
    window.addEventListener(BACK_EVENT, listener);

    return () => window.removeEventListener(BACK_EVENT, listener);
}
