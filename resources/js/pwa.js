import { registerSW } from 'virtual:pwa-register';

const HOUR = 60 * 60 * 1000;

/**
 * Registers the service worker in production builds and tracks updates.
 * A new version never takes over by itself: the walker might be mid-entry on
 * a hill, so the page shows an update bar and reloads only when asked.
 */
export function pwaStore() {
    return {
        offlineReady: false,
        needRefresh: false,
        updateRequired: false,
        buildId: __BUILD_ID__,
        updateSW: null,
        registration: null,

        init() {
            // The server speaks a newer API than this build: look for the new version now
            window.addEventListener('crwydro:update-required', () => {
                this.updateRequired = true;
                this.checkForUpdate();
            });

            if (!import.meta.env.PROD || !('serviceWorker' in navigator)) {
                return;
            }

            this.updateSW = registerSW({
                immediate: true,
                onOfflineReady: () => (this.offlineReady = true),
                onNeedRefresh: () => (this.needRefresh = true),
                onRegisteredSW: (url, registration) => {
                    this.registration = registration;
                    setInterval(() => this.checkForUpdate(), HOUR);
                    document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && this.checkForUpdate());
                },
            });
        },

        async checkForUpdate() {
            if (!navigator.onLine || !this.registration) {
                return;
            }

            try {
                await this.registration.update();
            } catch {
                // No signal or the server is unreachable: try again later
            }
        },

        update() {
            this.updateSW?.(true);
        },

        later() {
            this.needRefresh = false;
        },
    };
}
