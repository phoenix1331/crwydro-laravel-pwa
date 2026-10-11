import { db } from './db';

export const ENTRY_PUT = 'entry.put';
export const ENTRY_DELETE = 'entry.delete';
export const PHOTO_PUT = 'photo.put';

/**
 * Queue an upsert for an entry. Must run inside a transaction that includes
 * `db.outbox`. A pending put for the same entry is reused rather than
 * duplicated, because the request always sends the entry's latest state;
 * bumping `rev` tells an in-flight send that it is now stale.
 */
export async function queueEntryPut(entryId) {
    const existing = await db.outbox
        .where('refId')
        .equals(entryId)
        .filter((row) => row.kind === ENTRY_PUT)
        .first();

    if (existing) {
        return db.outbox.update(existing.seq, {
            rev: existing.rev + 1,
            failed: false,
            attempts: 0,
            nextAttemptAt: 0,
            lastError: null,
        });
    }

    return addRow(ENTRY_PUT, entryId, entryId);
}

export function queueEntryDelete(entryId) {
    return addRow(ENTRY_DELETE, entryId, entryId);
}

export function queuePhotoPut(photoId, entryId) {
    return addRow(PHOTO_PUT, photoId, entryId);
}

function addRow(kind, refId, entryId) {
    return db.outbox.add({
        kind,
        refId,
        entryId,
        rev: 1,
        attempts: 0,
        nextAttemptAt: 0,
        failed: false,
        lastError: null,
        createdAt: new Date().toISOString(),
    });
}

export function pendingRowsFor(entryId) {
    return db.outbox.where('entryId').equals(entryId).count();
}
