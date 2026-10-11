import { describe, expect, it } from 'vitest';
import { db } from '../../resources/js/data/db';
import { formatBytes, freeSyncedPhotos } from '../../resources/js/data/storage';

describe('freeSyncedPhotos', () => {
    it('drops full-size copies of synced photos only, keeping thumbnails', async () => {
        await db.photos.bulkAdd([
            { id: 'A', entryId: 'E', blob: new Blob([new Uint8Array(500)]), thumb: new Blob([new Uint8Array(20)]), syncStatus: 'synced' },
            { id: 'B', entryId: 'E', blob: new Blob([new Uint8Array(700)]), thumb: new Blob([new Uint8Array(20)]), syncStatus: 'local' },
        ]);

        expect(await freeSyncedPhotos()).toBe(500);

        const [synced, local] = await db.photos.bulkGet(['A', 'B']);
        expect(synced).toMatchObject({ blob: null, remote: true });
        expect(synced.thumb.size).toBe(20);
        expect(local.blob.size).toBe(700);
    });
});

describe('formatBytes', () => {
    it('uses short units', () => {
        expect(formatBytes(512)).toBe('512 byte');
        expect(formatBytes(2_400_000)).toBe('2.4 MB');
    });
});
