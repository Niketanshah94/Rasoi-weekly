import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { initializeAppFeel } from '@/lib/appFeel';
import { initializeBackButton } from '@/lib/backButton';
import { initializeOffline } from '@/lib/offline';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name.startsWith('onboarding/'):
            case name.startsWith('app/'):
            case name.startsWith('legal/'):
            case name.startsWith('auth/'):
                return null;

            // Customer settings use the app's own screens; Security stays in the settings frame.
            case name === 'settings/Household':
            case name === 'settings/Profile':
            case name === 'settings/Appearance':
                return null;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    // A Rasoi-green bar; the full loader is in lib/appFeel.ts.
    progress: {
        color: '#2E6A3E',
        delay: 150,
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// Installable app: offline copies of this week's plan and lists.
initializeOffline();

// Splash, loader between screens and the offline banner.
initializeAppFeel();

// Android back button with native behaviour.
initializeBackButton();
