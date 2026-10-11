export class NetworkError extends Error {}

/**
 * The API contract this build understands. Bump alongside
 * config('crwydro.api_version') when a cached client must update.
 */
export const API_VERSION = 1;

/**
 * Tell the page (or the worker's clients) the server speaks a newer API than
 * this cached build, so it can offer an update.
 */
function reportServerVersion(response) {
    const server = Number(response.headers?.get?.('X-Crwydro-Api'));

    if (server > API_VERSION || response.status === 426) {
        globalThis.dispatchEvent?.(new CustomEvent('crwydro:update-required', { detail: { server } }));
    }
}

/**
 * Thin fetch wrapper for the sync API. Relative URLs work from both the page
 * and the service worker, since both share the origin.
 */
export function createApi({ token = null, fetch: fetchImpl = globalThis.fetch.bind(globalThis) } = {}) {
    async function request(method, path, { body = undefined, contentType = 'application/json', timeout = 15000 } = {}) {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), timeout);
        const headers = { Accept: 'application/json', 'X-Crwydro-Client-Api': String(API_VERSION) };

        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        if (body !== undefined) {
            headers['Content-Type'] = contentType;
        }

        let response;

        try {
            response = await fetchImpl(path, {
                method,
                headers,
                body: body === undefined || contentType !== 'application/json' ? body : JSON.stringify(body),
                signal: controller.signal,
                cache: 'no-store',
            });
        } catch (error) {
            throw new NetworkError(error?.name === 'AbortError' ? 'Request timed out' : 'Network request failed');
        } finally {
            clearTimeout(timer);
        }

        reportServerVersion(response);

        const text = await response.text().catch(() => '');
        let data = null;

        try {
            data = text ? JSON.parse(text) : null;
        } catch {
            data = null;
        }

        return { status: response.status, ok: response.ok, data };
    }

    return {
        request,

        /**
         * `navigator.onLine` and the `online` event can claim a connection
         * that cannot reach the server, so confirm with a short ping first.
         */
        async ping(timeout = 3000) {
            try {
                const { ok, data } = await request('GET', '/api/ping', { timeout });

                return ok && data?.ok === true;
            } catch {
                return false;
            }
        },
    };
}

/**
 * Pull a readable message out of a Laravel error response.
 */
export function errorMessage({ status, data }) {
    const firstError = data?.errors ? Object.values(data.errors).flat()[0] : null;

    return firstError ?? data?.message ?? `Server responded with ${status}`;
}
