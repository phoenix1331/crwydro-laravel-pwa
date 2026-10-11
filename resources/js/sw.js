import { clientsClaim } from 'workbox-core';
import { cleanupOutdatedCaches, createHandlerBoundToURL, precacheAndRoute } from 'workbox-precaching';
import { NavigationRoute, registerRoute } from 'workbox-routing';

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

clientsClaim();
