import { clientsClaim } from 'workbox-core';
import { cleanupOutdatedCaches, createHandlerBoundToURL, precacheAndRoute } from 'workbox-precaching';
import { NavigationRoute, registerRoute } from 'workbox-routing';
import { BACKGROUND_SYNC_TAG } from './sync/background';
import { RESULT_INTERRUPTED, RESULT_OFFLINE, runSync } from './sync/processor';

// Build assets, icons and the `/` shell, versioned per build
precacheAndRoute(self.__WB_MANIFEST);
cleanupOutdatedCaches();

// Every view is a hash route on `/`, so navigations to the shell are served from precache
registerRoute(new NavigationRoute(createHandlerBoundToURL('/'), { allowlist: [/^\/(\?.*)?$/] }));

// The page asks the waiting worker to take over once the walker accepts the update
self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

/**
 * Background Sync (Chromium): drain the outbox even if the app is closed.
 * Rejecting tells the browser to try again later with its own backoff.
 */
self.addEventListener('sync', (event) => {
    if (event.tag !== BACKGROUND_SYNC_TAG) {
        return;
    }

    event.waitUntil(
        runSync().then((result) => {
            if (result.status === RESULT_OFFLINE || result.status === RESULT_INTERRUPTED) {
                throw new Error(`Sync incomplete: ${result.status}`);
            }
        }),
    );
});

clientsClaim();
