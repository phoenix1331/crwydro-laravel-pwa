import { db, getMeta, setMeta } from '../data/db';
import { SYNC_FAILED, SYNC_LOCAL, SYNC_SYNCED, SYNC_SYNCING } from '../data/entries';
import { ENTRY_DELETE, ENTRY_PUT, PHOTO_PUT } from '../data/outbox';
import { createApi, errorMessage, NetworkError } from './api';
import { backoffDelay } from './backoff';

export const RESULT_DONE = 'done';
export const RESULT_OFFLINE = 'offline';
export const RESULT_INTERRUPTED = 'interrupted';
export const RESULT_AUTH_REQUIRED = 'auth-required';
export const RESULT_UNAUTHENTICATED = 'unauthenticated';
export const RESULT_BUSY = 'busy';

const RETRYABLE = (status) => status === 409 || status === 408 || status === 429 || status >= 500;

function toServer(entry) {
    return {
        title: entry.title,
        notes: entry.notes || null,
        tag: entry.tag,
        lat: entry.lat,
        lng: entry.lng,
        accuracy_m: entry.accuracy,
        altitude_m: entry.altitude,
        recorded_at: entry.recordedAt,
        client_updated_at: entry.updatedAt,
    };
}

/**
 * Build the request for an outbox row, or null if its target no longer exists locally.
 */
async function requestFor(row) {
    if (row.kind === ENTRY_PUT) {
        const entry = await db.entries.get(row.refId);

        return entry ? ['PUT', `/api/entries/${entry.id}`, { body: toServer(entry) }] : null;
    }

    if (row.kind === ENTRY_DELETE) {
        return ['DELETE', `/api/entries/${row.refId}`, {}];
    }

    if (row.kind === PHOTO_PUT) {
        const photo = await db.photos.get(row.refId);

        return photo
            ? ['PUT', `/api/entries/${row.entryId}/photos/${photo.id}`, { body: photo.blob, contentType: 'image/jpeg', timeout: 60000 }]
            : null;
    }

    return null;
}

/**
 * Recompute an entry's badge from what is still queued for it. A synced
 * delete has nothing left to protect, so it is purged from the device.
 */
async function refreshEntryStatus(entryId) {
    const rows = await db.outbox.where('entryId').equals(entryId).toArray();
    const entry = await db.entries.get(entryId);

    if (!entry) {
        return;
    }

    if (!rows.length && entry.deletedAt) {
        await db.photos.where('entryId').equals(entryId).delete();
        await db.entries.delete(entryId);

        return;
    }

    const failed = rows.find((row) => row.failed);
    const status = failed ? SYNC_FAILED : rows.length ? SYNC_LOCAL : SYNC_SYNCED;

    await db.entries.update(entryId, { syncStatus: status, syncError: failed?.lastError ?? null });
}

/**
 * Remove a confirmed row, unless the entry was edited while the request was
 * in flight (rev changed), in which case the newer state still needs sending.
 */
async function complete(row, sentRev) {
    await db.transaction('rw', db.outbox, db.entries, db.photos, async () => {
        const current = await db.outbox.get(row.seq);

        if (current && current.rev === sentRev) {
            await db.outbox.delete(row.seq);

            if (row.kind === PHOTO_PUT) {
                await db.photos.update(row.refId, { syncStatus: SYNC_SYNCED });
            }
        }

        await refreshEntryStatus(row.entryId);
    });
}

async function retryLater(row, now, random, message) {
    await db.transaction('rw', db.outbox, db.entries, db.photos, async () => {
        const current = await db.outbox.get(row.seq);

        if (current) {
            const attempts = current.attempts + 1;

            await db.outbox.update(row.seq, {
                attempts,
                nextAttemptAt: now() + backoffDelay(attempts, random),
                lastError: message,
            });
        }

        await refreshEntryStatus(row.entryId);
    });
}

async function markFailed(row, message) {
    await db.transaction('rw', db.outbox, db.entries, db.photos, async () => {
        await db.outbox.update(row.seq, { failed: true, lastError: message });
        await refreshEntryStatus(row.entryId);
    });
}

async function release(row) {
    await db.transaction('rw', db.outbox, db.entries, db.photos, () => refreshEntryStatus(row.entryId));
}

/**
 * Drain the outbox in `seq` order. Once an entry's own put or delete is
 * waiting or failed, every later row for that entry is held back (its photos
 * need the entry on the server first), while other entries carry on. A photo
 * that is waiting or failed holds nothing back: the entry text and other
 * photos still go.
 */
export async function processOutbox({ fetch: fetchImpl, now = Date.now, random = Math.random } = {}) {
    const token = await getMeta('token');

    if (!token) {
        return { status: RESULT_UNAUTHENTICATED };
    }

    if (await getMeta('authRequired', false)) {
        return { status: RESULT_AUTH_REQUIRED };
    }

    // A badge left on `syncing` means a previous run was cut off (app closed mid-request)
    await db.entries.where('syncStatus').equals(SYNC_SYNCING).modify({ syncStatus: SYNC_LOCAL });

    const api = createApi({ token, fetch: fetchImpl });

    if (!(await api.ping())) {
        return { status: RESULT_OFFLINE };
    }

    const rows = await db.outbox.orderBy('seq').toArray();
    const held = new Set();
    const stats = { sent: 0, failed: 0, deferred: 0 };

    for (const queued of rows) {
        if (held.has(queued.entryId)) {
            continue;
        }

        const row = await db.outbox.get(queued.seq);

        if (!row) {
            continue;
        }

        const holdsEntry = row.kind !== PHOTO_PUT;

        if (row.failed || row.nextAttemptAt > now()) {
            if (holdsEntry) held.add(row.entryId);

            stats.deferred += row.failed ? 0 : 1;
            continue;
        }

        const request = await requestFor(row);

        if (!request) {
            await db.outbox.delete(row.seq);
            continue;
        }

        await db.entries.update(row.entryId, { syncStatus: SYNC_SYNCING });

        let response;

        try {
            response = await api.request(...request);
        } catch (error) {
            if (!(error instanceof NetworkError)) {
                throw error;
            }

            // Signal dropped mid-sync: keep the row, back off, and stop this run
            await retryLater(row, now, random, error.message);

            return { status: RESULT_INTERRUPTED, ...stats };
        }

        if (response.ok) {
            await complete(row, row.rev);
            stats.sent++;
            continue;
        }

        if (response.status === 401) {
            await setMeta('authRequired', true);
            await release(row);

            return { status: RESULT_AUTH_REQUIRED, ...stats };
        }

        if (holdsEntry) {
            held.add(row.entryId);
        }

        if (RETRYABLE(response.status)) {
            await retryLater(row, now, random, errorMessage(response));
            stats.deferred++;
            continue;
        }

        await markFailed(row, errorMessage(response));
        stats.failed++;
    }

    await setMeta('lastSyncAt', new Date(now()).toISOString());

    return { status: RESULT_DONE, ...stats };
}

/**
 * Run the processor at most once at a time across tabs and the service
 * worker, using the Web Locks API where available.
 */
let running = null;

export function runSync(options = {}) {
    const locks = globalThis.navigator?.locks;

    if (locks) {
        return locks.request('crwydro-sync', { ifAvailable: true }, (lock) =>
            lock ? processOutbox(options) : { status: RESULT_BUSY },
        );
    }

    if (running) {
        return Promise.resolve({ status: RESULT_BUSY });
    }

    running = processOutbox(options).finally(() => (running = null));

    return running;
}

/**
 * Earliest time a deferred row becomes due, so the page can schedule a retry.
 */
export async function nextRetryAt() {
    const rows = await db.outbox.filter((row) => !row.failed && row.nextAttemptAt > 0).toArray();

    return rows.length ? Math.min(...rows.map((row) => row.nextAttemptAt)) : null;
}

/**
 * Give every failed row another go, e.g. from a Retry button.
 */
export async function retryFailed() {
    await db.transaction('rw', db.outbox, db.entries, db.photos, async () => {
        const failed = await db.outbox.filter((row) => row.failed).toArray();

        for (const row of failed) {
            await db.outbox.update(row.seq, { failed: false, attempts: 0, nextAttemptAt: 0, lastError: null });
        }

        for (const entryId of new Set(failed.map((row) => row.entryId))) {
            await refreshEntryStatus(entryId);
        }
    });
}
