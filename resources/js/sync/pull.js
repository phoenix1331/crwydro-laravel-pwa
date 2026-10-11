import { db, getMeta, setMeta } from '../data/db';
import { SYNC_SYNCED } from '../data/entries';
import { createApi, NetworkError } from './api';

/**
 * Map a server entry onto the device's shape. `createdAt` drives list order,
 * so a pulled entry sorts by when it was recorded.
 */
function fromServer(remote, existing) {
    return {
        id: remote.id,
        title: remote.title,
        notes: remote.notes ?? '',
        tag: remote.tag,
        routeId: remote.route_id ?? null,
        lat: remote.lat,
        lng: remote.lng,
        accuracy: remote.accuracy_m,
        altitude: remote.altitude_m,
        recordedAt: remote.recorded_at,
        createdAt: existing?.createdAt ?? remote.recorded_at,
        updatedAt: remote.client_updated_at,
        deletedAt: null,
        syncStatus: SYNC_SYNCED,
        syncError: null,
    };
}

/**
 * Apply one pulled entry. Anything with changes still queued on this device
 * is left alone: the device's copy wins and the push will reach the server
 * (which resolves it last-write-wins). Otherwise the server copy is taken
 * if it is newer, and deletes remove the entry from the device.
 */
async function apply(remote) {
    const pending = await db.outbox.where('entryId').equals(remote.id).count();

    if (pending) {
        return 'skipped';
    }

    const local = await db.entries.get(remote.id);

    if (remote.deleted_at) {
        if (local) {
            await db.photos.where('entryId').equals(remote.id).delete();
            await db.entries.delete(remote.id);

            return 'deleted';
        }

        return 'ignored';
    }

    const newer = !local || Date.parse(remote.client_updated_at) > Date.parse(local.updatedAt);

    if (newer) {
        await db.entries.put(fromServer(remote, local));
    }

    // Photo metadata only: Blobs download separately so a pull never stalls on images
    const known = new Set(await db.photos.where('entryId').equals(remote.id).primaryKeys());

    for (const photo of remote.photos ?? []) {
        if (!known.has(photo.id)) {
            await db.photos.add({
                id: photo.id,
                entryId: remote.id,
                blob: null,
                thumb: null,
                width: photo.width,
                height: photo.height,
                bytes: photo.bytes,
                createdAt: photo.created_at,
                syncStatus: SYNC_SYNCED,
                remote: true,
            });
        }
    }

    return newer ? 'applied' : 'ignored';
}

/**
 * Pull everything changed on the server since the stored cursor, page by
 * page. Each page and its cursor are written in one transaction, so an
 * interrupted pull resumes from the last complete page.
 */
export async function pullChanges({ fetch: fetchImpl } = {}) {
    const token = await getMeta('token');

    if (!token) {
        return { status: 'unauthenticated', applied: 0 };
    }

    const api = createApi({ token, fetch: fetchImpl });
    const stats = { applied: 0, deleted: 0, skipped: 0 };
    let cursor = await getMeta('pullCursor');

    for (;;) {
        let response;

        try {
            response = await api.request('GET', cursor ? `/api/sync?since=${encodeURIComponent(cursor)}` : '/api/sync', { timeout: 30000 });
        } catch (error) {
            if (error instanceof NetworkError) {
                return { status: 'interrupted', ...stats };
            }

            throw error;
        }

        if (response.status === 401) {
            await setMeta('authRequired', true);

            return { status: 'auth-required', ...stats };
        }

        if (response.status === 422) {
            // Cursor rejected (e.g. format changed): start again from the beginning
            cursor = null;
            await setMeta('pullCursor', null);
            continue;
        }

        if (!response.ok) {
            return { status: 'error', ...stats };
        }

        const { data, next_cursor: next, has_more: hasMore } = response.data;

        await db.transaction('rw', db.entries, db.photos, db.outbox, db.meta, async () => {
            for (const remote of data) {
                const result = await apply(remote);

                if (result in stats) {
                    stats[result]++;
                }
            }

            await setMeta('pullCursor', next);
        });

        cursor = next;

        if (!hasMore) {
            return { status: 'done', ...stats };
        }
    }
}

/**
 * Download thumbnails for pulled photos, a few at a time. Full-size images
 * are fetched only when the walker opens one (`fetchFullPhoto`).
 */
export async function downloadThumbnails({ fetch: fetchImpl = globalThis.fetch.bind(globalThis), limit = 20 } = {}) {
    const token = await getMeta('token');

    if (!token) {
        return 0;
    }

    const missing = await db.photos.filter((photo) => photo.remote && !photo.thumb).limit(limit).toArray();
    let downloaded = 0;

    for (const photo of missing) {
        const blob = await fetchImage(fetchImpl, token, `/api/photos/${photo.id}/thumb`);

        if (!blob) {
            break;
        }

        await db.photos.update(photo.id, { thumb: blob });
        downloaded++;
    }

    return downloaded;
}

export async function fetchFullPhoto(photoId, { fetch: fetchImpl = globalThis.fetch.bind(globalThis) } = {}) {
    const photo = await db.photos.get(photoId);

    if (!photo || photo.blob) {
        return photo?.blob ?? null;
    }

    const token = await getMeta('token');
    const blob = token ? await fetchImage(fetchImpl, token, `/api/photos/${photoId}`) : null;

    if (blob) {
        await db.photos.update(photoId, { blob });
    }

    return blob;
}

async function fetchImage(fetchImpl, token, path) {
    try {
        const response = await fetchImpl(path, { headers: { Authorization: `Bearer ${token}` } });

        return response.ok ? await response.blob() : null;
    } catch {
        return null;
    }
}
