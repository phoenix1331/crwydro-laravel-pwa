import { describe, expect, it } from 'vitest';
import { db } from '../../resources/js/data/db';
import {
    createEntry,
    deleteEntry,
    getEntry,
    listEntries,
    unsyncedCount,
    updateEntry,
} from '../../resources/js/data/entries';
import { ENTRY_DELETE, ENTRY_PUT } from '../../resources/js/data/outbox';

const poppy = {
    title: 'Pabi Cymreig',
    notes: 'By the stream below Llyn Cau',
    tag: 'flower',
    location: { lat: 52.6893, lng: -3.8936, accuracy: 8, altitude: 412 },
};

describe('createEntry', () => {
    it('stores the entry with a ulid and queues one outbox row', async () => {
        const entry = await createEntry(poppy);

        expect(entry.id).toMatch(/^[0-9A-HJKMNP-TV-Z]{26}$/);
        expect(entry.syncStatus).toBe('local');
        expect(entry.lat).toBe(52.6893);

        const rows = await db.outbox.toArray();
        expect(rows).toHaveLength(1);
        expect(rows[0]).toMatchObject({ kind: ENTRY_PUT, refId: entry.id, entryId: entry.id, rev: 1 });
    });

    it('allows an entry without a location', async () => {
        const entry = await createEntry({ title: 'Mist', tag: 'view' });

        expect(entry.lat).toBeNull();
        expect(entry.accuracy).toBeNull();
    });

    it('rejects a missing title or unknown tag and writes nothing', async () => {
        await expect(createEntry({ title: '  ', tag: 'flower' })).rejects.toThrow('title');
        await expect(createEntry({ title: 'Dragon', tag: 'dragon' })).rejects.toThrow('tag');

        expect(await db.entries.count()).toBe(0);
        expect(await db.outbox.count()).toBe(0);
    });
});

describe('updateEntry', () => {
    it('reuses the pending put and bumps its revision', async () => {
        const entry = await createEntry(poppy);

        await updateEntry(entry.id, { title: 'Pabi Cymreig (Welsh poppy)' });
        await updateEntry(entry.id, { notes: 'Three clumps' });

        const rows = await db.outbox.toArray();
        expect(rows).toHaveLength(1);
        expect(rows[0].rev).toBe(3);
        expect((await getEntry(entry.id)).title).toBe('Pabi Cymreig (Welsh poppy)');
    });

    it('always moves updatedAt forward, even within the same millisecond', async () => {
        const entry = await createEntry(poppy);
        const first = await updateEntry(entry.id, { notes: 'a' });
        const second = await updateEntry(entry.id, { notes: 'b' });

        expect(Date.parse(first.updatedAt)).toBeGreaterThan(Date.parse(entry.updatedAt));
        expect(Date.parse(second.updatedAt)).toBeGreaterThan(Date.parse(first.updatedAt));
    });

    it('only changes editable fields', async () => {
        const entry = await createEntry(poppy);

        const updated = await updateEntry(entry.id, { lat: 0, id: 'nope', tag: 'bird' });

        expect(updated.lat).toBe(52.6893);
        expect(updated.id).toBe(entry.id);
        expect(updated.tag).toBe('bird');
    });

    it('queues a fresh put after the entry synced', async () => {
        const entry = await createEntry(poppy);
        await db.outbox.clear();
        await db.entries.update(entry.id, { syncStatus: 'synced' });

        await updateEntry(entry.id, { title: 'Edited on the train home' });

        expect(await db.outbox.count()).toBe(1);
        expect((await getEntry(entry.id)).syncStatus).toBe('local');
    });

    it('revives a failed put so the walker can fix and retry', async () => {
        const entry = await createEntry(poppy);
        const [row] = await db.outbox.toArray();
        await db.outbox.update(row.seq, { failed: true, attempts: 3, lastError: '422' });

        await updateEntry(entry.id, { title: 'Fixed' });

        const [revived] = await db.outbox.toArray();
        expect(revived).toMatchObject({ failed: false, attempts: 0, lastError: null });
    });
});

describe('deleteEntry', () => {
    it('soft deletes, hides the entry and queues a delete after the put', async () => {
        const entry = await createEntry(poppy);

        await deleteEntry(entry.id);

        expect(await getEntry(entry.id)).toBeNull();
        expect((await db.entries.get(entry.id)).deletedAt).not.toBeNull();
        expect((await db.outbox.orderBy('seq').toArray()).map((row) => row.kind)).toEqual([ENTRY_PUT, ENTRY_DELETE]);
    });

    it('is a no-op the second time', async () => {
        const entry = await createEntry(poppy);

        await deleteEntry(entry.id);
        await deleteEntry(entry.id);

        expect(await db.outbox.count()).toBe(2);
    });
});

describe('listEntries', () => {
    it('lists newest first, filters by tag and hides deleted entries', async () => {
        const first = await createEntry(poppy);
        const second = await createEntry({ title: 'Barcud coch', tag: 'bird' });
        const third = await createEntry({ title: 'Chanterelle', tag: 'fungus' });
        await deleteEntry(third.id);

        expect((await listEntries()).map((entry) => entry.id)).toEqual([second.id, first.id]);
        expect((await listEntries({ tag: 'bird' })).map((entry) => entry.id)).toEqual([second.id]);
    });
});

describe('unsyncedCount', () => {
    it('counts everything not confirmed by the server, including deletes', async () => {
        const a = await createEntry(poppy);
        const b = await createEntry({ title: 'Barcud coch', tag: 'bird' });
        await db.entries.update(a.id, { syncStatus: 'synced' });
        await deleteEntry(b.id);

        expect(await unsyncedCount()).toBe(1);
    });
});
