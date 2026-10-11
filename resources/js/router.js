/**
 * Minimal hash router: every view lives under `/#/...` so the server only
 * ever serves (and the service worker only ever precaches) the `/` shell.
 */
const routes = [
    { name: 'entries', pattern: /^\/?$/ },
    { name: 'entries', pattern: /^\/entries\/?$/ },
    { name: 'new', pattern: /^\/entries\/new\/?$/ },
    { name: 'entry', pattern: /^\/entries\/([0-9A-HJKMNP-TV-Z]{26})\/?$/ },
    { name: 'edit', pattern: /^\/entries\/([0-9A-HJKMNP-TV-Z]{26})\/edit\/?$/ },
    { name: 'walks', pattern: /^\/walks\/?$/ },
    { name: 'map', pattern: /^\/map(?:\/([a-z-]+))?\/?$/ },
    { name: 'sync', pattern: /^\/sync\/?$/ },
    { name: 'login', pattern: /^\/login\/?$/ },
];

export function resolve(hash) {
    const path = hash.replace(/^#/, '') || '/';

    for (const route of routes) {
        const match = path.match(route.pattern);

        if (match) {
            return { name: route.name, params: { id: match[1] ?? null }, path };
        }
    }

    return { name: 'entries', params: { id: null }, path: '/' };
}

export function routerStore() {
    return {
        name: 'entries',
        params: { id: null },
        path: '/',

        init() {
            this.sync();
            window.addEventListener('hashchange', () => this.sync());
        },

        sync() {
            Object.assign(this, resolve(window.location.hash));
            window.scrollTo({ top: 0 });
        },

        go(path) {
            window.location.hash = path;
        },

        is(...names) {
            return names.includes(this.name);
        },
    };
}
