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

            registerSW({
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

        /**
         * Activate the waiting version and reload once it controls the page.
         * Does not rely on the plugin's reload, which only fires for an update
         * this tab started: with several tabs open, another tab may already
         * have activated it, leaving nothing waiting here. In that case, or if
         * activation stalls, a plain reload picks up the new version.
         */
        async update() {
            const registration = this.registration ?? (await navigator.serviceWorker?.getRegistration());
            const waiting = registration?.waiting;

            if (!waiting) {
                window.location.reload();

                return;
            }

            navigator.serviceWorker.addEventListener('controllerchange', () => window.location.reload(), { once: true });
            waiting.postMessage({ type: 'SKIP_WAITING' });
            setTimeout(() => window.location.reload(), 3000);
        },

        later() {
            this.needRefresh = false;
        },
    };
}
