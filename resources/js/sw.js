import { clientsClaim } from 'workbox-core';
import { cleanupOutdatedCaches, createHandlerBoundToURL, precacheAndRoute } from 'workbox-precaching';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { ExpirationPlugin } from 'workbox-expiration';
import { NavigationRoute, registerRoute } from 'workbox-routing';
import { CacheFirst } from 'workbox-strategies';
import { BACKGROUND_SYNC_TAG } from './sync/background';
import { RESULT_INTERRUPTED, RESULT_OFFLINE, runSync } from './sync/processor';

// Build assets, icons and the `/` shell, versioned per build
precacheAndRoute(self.__WB_MANIFEST);
cleanupOutdatedCaches();

// Every view is a hash route on `/`, so navigations to the shell are served from precache
registerRoute(new NavigationRoute(createHandlerBoundToURL('/'), { allowlist: [/^\/(\?.*)?$/] }));

// OpenStreetMap tiles: cached as the walker views them (never bulk-prefetched,
// per the OSM tile usage policy), so areas seen with signal work offline
registerRoute(
    ({ url }) => url.hostname === 'tile.openstreetmap.org',
    new CacheFirst({
        cacheName: 'osm-tiles',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxEntries: 3000, maxAgeSeconds: 30 * 24 * 60 * 60, purgeOnQuotaError: true }),
        ],
    }),
);

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
