export const BACKGROUND_SYNC_TAG = 'crwydro-outbox';

/**
 * Ask the browser to wake the service worker when connectivity returns.
 * Chromium only; Safari and Firefox rely on the in-page triggers instead.
 */
export async function requestBackgroundSync() {
    try {
        const registration = await navigator.serviceWorker?.ready;

        await registration?.sync?.register(BACKGROUND_SYNC_TAG);
    } catch {
        // Unsupported or permission denied: the page triggers still cover it
    }
}

export const backgroundSyncSupported = () => typeof window !== 'undefined' && 'SyncManager' in window;
