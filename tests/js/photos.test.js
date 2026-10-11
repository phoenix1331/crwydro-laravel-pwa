import { describe, expect, it } from 'vitest';
import { db } from '../../resources/js/data/db';
import { createEntry } from '../../resources/js/data/entries';
import { ENTRY_PUT, PHOTO_PUT } from '../../resources/js/data/outbox';
import { addPhotos, coverPhotos, photosFor, removeUnsyncedPhoto } from '../../resources/js/data/photos';

const photo = (size = 1000) => ({ blob: new Blob([new Uint8Array(size)]), thumb: new Blob([new Uint8Array(10)]), width: 1600, height: 1200 });

describe('photos', () => {
    it('stores photos with the entry and queues them after the entry put', async () => {
        const entry = await createEntry({ title: 'Llyn Cau', tag: 'view', photos: [photo(500), photo(700)] });

        const rows = await db.outbox.orderBy('seq').toArray();
        expect(rows.map((row) => row.kind)).toEqual([ENTRY_PUT, PHOTO_PUT, PHOTO_PUT]);
        expect(rows.every((row) => row.entryId === entry.id)).toBe(true);

        const stored = await photosFor(entry.id);
        expect(stored.map((p) => p.bytes)).toEqual([500, 700]);
        expect(stored[0]).toMatchObject({ width: 1600, height: 1200, syncStatus: 'local' });
    });

    it('limits an entry to four photos and writes nothing when over', async () => {
        await expect(createEntry({ title: 'Too many', tag: 'view', photos: Array.from({ length: 5 }, () => photo()) })).rejects.toThrow('at most 4');

        expect(await db.entries.count()).toBe(0);
        expect(await db.photos.count()).toBe(0);
        expect(await db.outbox.count()).toBe(0);
    });

    it('adds photos to an existing entry up to the limit', async () => {
        const entry = await createEntry({ title: 'Tryfan', tag: 'view', photos: [photo(), photo(), photo()] });
        await db.entries.update(entry.id, { syncStatus: 'synced' });

        await addPhotos(entry.id, [photo()]);
        await expect(addPhotos(entry.id, [photo()])).rejects.toThrow('at most 4');

        expect(await db.photos.where('entryId').equals(entry.id).count()).toBe(4);
        expect((await db.entries.get(entry.id)).syncStatus).toBe('local');
    });

    it('removes an unsynced photo along with its queued upload, but never a synced one', async () => {
        const entry = await createEntry({ title: 'Pen y Fan', tag: 'view', photos: [photo(), photo()] });
        const [first, second] = await photosFor(entry.id);
        await db.photos.update(second.id, { syncStatus: 'synced' });

        expect(await removeUnsyncedPhoto(first.id)).toBe(true);
        expect(await removeUnsyncedPhoto(second.id)).toBe(false);

        expect((await photosFor(entry.id)).map((p) => p.id)).toEqual([second.id]);
        expect(await db.outbox.where('refId').equals(first.id).count()).toBe(0);
    });

    it('picks the first photo of each entry as its cover', async () => {
        const a = await createEntry({ title: 'A', tag: 'view', photos: [photo(1), photo(2)] });
        const b = await createEntry({ title: 'B', tag: 'view' });

        const covers = await coverPhotos([a.id, b.id]);

        expect(covers[a.id].bytes).toBe(1);
        expect(covers[b.id]).toBeUndefined();
    });
});
