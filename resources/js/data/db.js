import Dexie from 'dexie';

/**
 * The device is the source of truth for anything not yet synced.
 *
 * entries: one row per journal entry, keyed by a client-minted ULID
 * photos:  resized photo Blobs, keyed by their own ULID
 * outbox:  ordered queue of pending server writes; a row is removed only once the server confirms it
 * meta:    key/value settings such as the API token and pull cursor
 */
export const db = new Dexie('crwydro');

db.version(1).stores({
    entries: 'id, createdAt, updatedAt, tag, syncStatus, deletedAt',
    photos: 'id, entryId, syncStatus',
    outbox: '++seq, kind, refId, entryId, attempts',
    meta: 'key',
});

// Entries can be attached to a featured walk. Upgrading runs on the device
// the first time the new app version opens, so it must never lose data.
db.version(2)
    .stores({
        entries: 'id, createdAt, updatedAt, tag, syncStatus, deletedAt, routeId',
    })
    .upgrade((tx) =>
        tx
            .table('entries')
            .toCollection()
            .modify((entry) => {
                entry.routeId ??= null;
            }),
    );

export async function getMeta(key, fallback = null) {
    const row = await db.meta.get(key);

    return row ? row.value : fallback;
}

export function setMeta(key, value) {
    return db.meta.put({ key, value });
}
