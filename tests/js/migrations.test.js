import Dexie from 'dexie';
import { describe, expect, it } from 'vitest';
import { db } from '../../resources/js/data/db';
import { createEntry, updateEntry } from '../../resources/js/data/entries';

describe('dexie schema upgrades', () => {
    it('upgrades a version 1 device without losing entries or queued uploads', async () => {
        db.close();
        await Dexie.delete('crwydro');

        const v1 = new Dexie('crwydro');
        v1.version(1).stores({
            entries: 'id, createdAt, updatedAt, tag, syncStatus, deletedAt',
            photos: 'id, entryId, syncStatus',
            outbox: '++seq, kind, refId, entryId, attempts',
            meta: 'key',
        });
        await v1.entries.add({ id: '01K7A3Q9X2M4N6P8R0S2T4V6W8', title: 'Offline before the update', tag: 'flower', createdAt: '2026-10-11T10:00:00.000Z', syncStatus: 'local', deletedAt: null });
        await v1.outbox.add({ kind: 'entry.put', refId: '01K7A3Q9X2M4N6P8R0S2T4V6W8', entryId: '01K7A3Q9X2M4N6P8R0S2T4V6W8', rev: 1, attempts: 0 });
        v1.close();

        await db.open();

        expect(db.verno).toBe(2);
        expect(await db.entries.get('01K7A3Q9X2M4N6P8R0S2T4V6W8')).toMatchObject({ title: 'Offline before the update', routeId: null });
        expect(await db.outbox.count()).toBe(1);
        expect(await db.entries.where('routeId').equals('clawdd-offa').count()).toBe(0);
    });
});

describe('walks', () => {
    it('attaches an entry to a featured walk and lets it change later', async () => {
        const entry = await createEntry({ title: 'Barcud coch', tag: 'bird', routeId: 'llwybr-glyndwr' });

        const updated = await updateEntry(entry.id, { routeId: 'clawdd-offa' });

        expect(updated.routeId).toBe('clawdd-offa');
        expect(await db.entries.where('routeId').equals('clawdd-offa').count()).toBe(1);
    });

    it('rejects an unknown walk', async () => {
        await expect(createEntry({ title: 'Somewhere', tag: 'view', routeId: 'pennine-way' })).rejects.toThrow('Unknown walk');
    });
});
