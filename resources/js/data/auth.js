import { t } from '../i18n';
import { db, setMeta } from './db';
import { createApi, errorMessage, NetworkError } from '../sync/api';

function deviceName() {
    const platform = navigator.userAgentData?.platform || (/(iPhone|iPad|Android|Mac|Windows|Linux)/.exec(navigator.userAgent)?.[1] ?? 'browser');

    return `Crwydro on ${platform}`;
}

/**
 * Exchange credentials for a long-lived token. Logging in again after a 401
 * only replaces the token: entries and the outbox are never touched.
 */
export async function login(email, password, { fetch: fetchImpl } = {}) {
    let response;

    try {
        response = await createApi({ fetch: fetchImpl }).request('POST', '/api/login', {
            body: { email, password, device_name: deviceName() },
        });
    } catch (error) {
        if (error instanceof NetworkError) {
            throw new Error(t('login.needs_signal'));
        }

        throw error;
    }

    if (!response.ok) {
        throw new Error(response.status === 429 ? t('login.too_many') : errorMessage(response));
    }

    await db.transaction('rw', db.meta, async () => {
        await setMeta('token', response.data.token);
        await setMeta('user', response.data.user);
        await setMeta('authRequired', false);
    });

    return response.data.user;
}

export async function authState() {
    const rows = await db.meta.bulkGet(['token', 'user', 'authRequired']);
    const [token, user, authRequired] = rows.map((row) => row?.value ?? null);

    return { loggedIn: Boolean(token), user, authRequired: Boolean(authRequired) };
}
