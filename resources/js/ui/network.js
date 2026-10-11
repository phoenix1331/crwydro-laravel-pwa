/**
 * Tracks the browser's view of connectivity. `navigator.onLine` can be true
 * with no real signal, so sync still pings the server before trusting it.
 */
export function networkStore() {
    return {
        online: navigator.onLine,

        init() {
            window.addEventListener('online', () => (this.online = true));
            window.addEventListener('offline', () => (this.online = false));
        },
    };
}
