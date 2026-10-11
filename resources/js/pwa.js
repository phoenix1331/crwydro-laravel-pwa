import { registerSW } from 'virtual:pwa-register';

/**
 * Registers the service worker in production builds and exposes update state.
 * `updateSW(true)` tells the waiting worker to skip waiting and reloads the page.
 */
export function pwaStore() {
    return {
        offlineReady: false,
        needRefresh: false,
        buildId: __BUILD_ID__,
        updateSW: null,

        init() {
            if (!import.meta.env.PROD || !('serviceWorker' in navigator)) {
                return;
            }

            this.updateSW = registerSW({
                immediate: true,
                onOfflineReady: () => (this.offlineReady = true),
                onNeedRefresh: () => (this.needRefresh = true),
            });
        },

        update() {
            this.updateSW?.(true);
        },
    };
}
