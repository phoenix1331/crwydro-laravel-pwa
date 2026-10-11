import { db } from './db';
import { ulid } from './ids';
import { PHOTO_PUT, queuePhotoPut } from './outbox';
import { nowIso } from './time';

export const MAX_PHOTOS = 4;

/**
 * Store resized photos for an entry and queue one upload each. Must run inside
 * a transaction over entries, photos and outbox. Each photo is its own outbox
 * row, so one slow or failed upload never holds up the entry text.
 *
 * @param {Array<{blob: Blob, thumb: Blob, width: number, height: number}>} photos
 */
export async function attachPhotos(entryId, photos) {
    const existing = await db.photos.where('entryId').equals(entryId).count();

    if (existing + photos.length > MAX_PHOTOS) {
        throw new Error(`An entry can have at most ${MAX_PHOTOS} photos.`);
    }

    const rows = photos.map(({ blob, thumb, width, height }) => ({
        id: ulid(),
        entryId,
        blob,
        thumb,
        width,
        height,
        bytes: blob.size,
        createdAt: nowIso(),
        syncStatus: 'local',
    }));

    await db.photos.bulkAdd(rows);

    for (const row of rows) {
        await queuePhotoPut(row.id, entryId);
    }

    return rows;
}

export async function addPhotos(entryId, photos) {
    return db.transaction('rw', db.entries, db.photos, db.outbox, async () => {
        const entry = await db.entries.get(entryId);

        if (!entry || entry.deletedAt) {
            throw new Error('Entry not found.');
        }

        const rows = await attachPhotos(entryId, photos);
        await db.entries.update(entryId, { syncStatus: 'local', syncError: null });

        return rows;
    });
}

/**
 * Only photos the server has never seen can be removed in v1; there is no
 * photo delete endpoint yet.
 */
export async function removeUnsyncedPhoto(photoId) {
    return db.transaction('rw', db.photos, db.outbox, async () => {
        const photo = await db.photos.get(photoId);

        if (!photo || photo.syncStatus === 'synced') {
            return false;
        }

        await db.outbox.where('refId').equals(photoId).filter((row) => row.kind === PHOTO_PUT).delete();
        await db.photos.delete(photoId);

        return true;
    });
}

export function photosFor(entryId) {
    return db.photos.where('entryId').equals(entryId).sortBy('createdAt');
}

/**
 * First photo per entry, for list thumbnails.
 */
export async function coverPhotos(entryIds) {
    const photos = await db.photos.where('entryId').anyOf(entryIds).sortBy('createdAt');
    const covers = {};

    for (const photo of photos) {
        covers[photo.entryId] ??= photo;
    }

    return covers;
}
