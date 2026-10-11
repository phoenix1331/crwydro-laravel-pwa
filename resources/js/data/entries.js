import { db } from './db';
import { ulid } from './ids';
import { queueEntryDelete, queueEntryPut } from './outbox';
import { attachPhotos } from './photos';
import { TAG_KEYS } from './tags';
import { ROUTE_IDS } from './walks';
import { nextTimestamp, nowIso } from './time';

export const SYNC_LOCAL = 'local';
export const SYNC_SYNCING = 'syncing';
export const SYNC_SYNCED = 'synced';
export const SYNC_FAILED = 'failed';

const EDITABLE = ['title', 'notes', 'tag', 'routeId'];

function validate({ title, tag, routeId = null }) {
    if (routeId !== null && !ROUTE_IDS.includes(routeId)) {
        throw new Error(`Unknown walk: ${routeId}`);
    }

    if (!title || !String(title).trim()) {
        throw new Error('A title is required.');
    }

    if (!TAG_KEYS.includes(tag)) {
        throw new Error(`Unknown tag: ${tag}`);
    }
}

/**
 * Save a new entry (and any photos) and queue it for upload in one
 * transaction, so nothing can exist on the device without its outbox row.
 * The entry's put is queued first, so the server always has the entry
 * before its photos arrive.
 */
export async function createEntry({ title, notes = '', tag, routeId = null, location = null, recordedAt = null, photos = [] }) {
    validate({ title, tag, routeId });

    const now = nowIso();
    const entry = {
        id: ulid(),
        title: String(title).trim(),
        notes: String(notes ?? '').trim(),
        tag,
        routeId,
        lat: location?.lat ?? null,
        lng: location?.lng ?? null,
        accuracy: location?.accuracy ?? null,
        altitude: location?.altitude ?? null,
        recordedAt: recordedAt ?? now,
        createdAt: now,
        updatedAt: now,
        deletedAt: null,
        syncStatus: SYNC_LOCAL,
        syncError: null,
    };

    await db.transaction('rw', db.entries, db.photos, db.outbox, async () => {
        await db.entries.add(entry);
        await queueEntryPut(entry.id);

        if (photos.length) {
            await attachPhotos(entry.id, photos);
        }
    });

    return entry;
}

export async function updateEntry(id, changes) {
    return db.transaction('rw', db.entries, db.outbox, async () => {
        const entry = await db.entries.get(id);

        if (!entry || entry.deletedAt) {
            throw new Error('Entry not found.');
        }

        const patch = Object.fromEntries(Object.entries(changes).filter(([key]) => EDITABLE.includes(key)));
        const next = { ...entry, ...patch };

        if (typeof next.title === 'string') next.title = next.title.trim();
        if (typeof next.notes === 'string') next.notes = next.notes.trim();

        validate(next);

        next.updatedAt = nextTimestamp(entry.updatedAt);
        next.syncStatus = SYNC_LOCAL;
        next.syncError = null;

        await db.entries.put(next);
        await queueEntryPut(id);

        return next;
    });
}

/**
 * Soft delete: the row stays on the device until the server has the delete.
 */
export async function deleteEntry(id) {
    return db.transaction('rw', db.entries, db.outbox, async () => {
        const entry = await db.entries.get(id);

        if (!entry || entry.deletedAt) {
            return;
        }

        const updatedAt = nextTimestamp(entry.updatedAt);

        await db.entries.update(id, {
            deletedAt: updatedAt,
            updatedAt,
            syncStatus: SYNC_LOCAL,
            syncError: null,
        });
        await queueEntryDelete(id);
    });
}

export async function getEntry(id) {
    const entry = await db.entries.get(id);

    return entry && !entry.deletedAt ? entry : null;
}

/**
 * Newest first, optionally filtered by tag, excluding soft-deleted entries.
 */
export async function listEntries({ tag = null } = {}) {
    const entries = await db.entries
        .orderBy('createdAt')
        .reverse()
        .filter((entry) => !entry.deletedAt && (!tag || entry.tag === tag))
        .toArray();

    return entries;
}

/**
 * Entries with local changes the server has not confirmed, including deletes.
 */
export function unsyncedCount() {
    return db.entries.where('syncStatus').notEqual(SYNC_SYNCED).count();
}
