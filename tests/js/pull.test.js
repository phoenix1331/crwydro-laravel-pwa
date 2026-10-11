import { beforeEach, describe, expect, it } from 'vitest';
import { db, getMeta, setMeta } from '../../resources/js/data/db';
import { createEntry, updateEntry } from '../../resources/js/data/entries';
import { downloadThumbnails, fetchFullPhoto, pullChanges } from '../../resources/js/sync/pull';

const ID = '01K7C000000000000000000001';

function remote(overrides = {}) {
    return {
        id: ID,
        title: 'Barcud coch (Red kite)',
        notes: null,
        tag: 'bird',
        lat: 51.879,
        lng: -3.444,
        accuracy_m: 6,
        altitude_m: null,
        route_id: 'pen-y-fan-pont-ar-daf',
        recorded_at: '2026-10-09T13:05:00.000Z',
        client_updated_at: '2026-10-09T13:05:00.000Z',
        updated_at: '2026-10-09T13:05:01.000Z',
        deleted_at: null,
        photos: [],
        ...overrides,
    };
}

/**
 * Serves pages of /api/sync in order, recording the cursors asked for.
 */
function syncServer(pages, { images = {} } = {}) {
    const asked = [];

    const fetch = async (url) => {
        if (url.startsWith('/api/sync')) {
            asked.push(new URL(url, 'http://x').searchParams.get('since'));
            const page = pages.shift() ?? { data: [], next_cursor: asked.at(-1), has_more: false };

            return page instanceof Error ? Promise.reject(page) : new Response(JSON.stringify(page.body ?? page), { status: page.status ?? 200 });
        }

        if (url in images) {
            return new Response(images[url]);
        }

        return new Response('', { status: 404 });
    };

    return { fetch, asked };
}

beforeEach(async () => {
    await setMeta('token', 'test-token');
});

describe('pullChanges', () => {
    it('catches a new device up from the beginning and stores the cursor', async () => {
        const server = syncServer([{ data: [remote()], next_cursor: 'c1', has_more: false }]);

        const result = await pullChanges({ fetch: server.fetch });

        expect(result).toMatchObject({ status: 'done', applied: 1 });
        expect(server.asked).toEqual([null]);
        expect(await db.entries.get(ID)).toMatchObject({ title: 'Barcud coch (Red kite)', routeId: 'pen-y-fan-pont-ar-daf', syncStatus: 'synced', createdAt: '2026-10-09T13:05:00.000Z' });
        expect(await getMeta('pullCursor')).toBe('c1');
        expect(await db.outbox.count()).toBe(0);
    });

    it('follows has_more across pages and resumes from the stored cursor', async () => {
        await setMeta('pullCursor', 'c0');
        const server = syncServer([
            { data: [remote()], next_cursor: 'c1', has_more: true },
            { data: [remote({ id: '01K7C000000000000000000002' })], next_cursor: 'c2', has_more: false },
        ]);

        await pullChanges({ fetch: server.fetch });

        expect(server.asked).toEqual(['c0', 'c1']);
        expect(await db.entries.count()).toBe(2);
        expect(await getMeta('pullCursor')).toBe('c2');
    });

    it('keeps completed pages when the connection drops mid-pull', async () => {
        const server = syncServer([{ data: [remote()], next_cursor: 'c1', has_more: true }, new TypeError('Failed to fetch')]);

        expect((await pullChanges({ fetch: server.fetch })).status).toBe('interrupted');
        expect(await getMeta('pullCursor')).toBe('c1');
        expect(await db.entries.count()).toBe(1);
    });

    it('takes a newer server copy but ignores an older one', async () => {
        await pullChanges({ fetch: syncServer([{ data: [remote()], next_cursor: 'c1', has_more: false }]).fetch });

        await pullChanges({ fetch: syncServer([{ data: [remote({ title: 'Older', client_updated_at: '2026-10-01T00:00:00.000Z' })], next_cursor: 'c2', has_more: false }]).fetch });
        expect((await db.entries.get(ID)).title).toBe('Barcud coch (Red kite)');

        await pullChanges({ fetch: syncServer([{ data: [remote({ title: 'Edited on the laptop', client_updated_at: '2026-10-10T09:00:00.000Z' })], next_cursor: 'c3', has_more: false }]).fetch });
        expect((await db.entries.get(ID)).title).toBe('Edited on the laptop');
    });

    it('never overwrites an entry with changes still waiting to upload', async () => {
        const entry = await createEntry({ title: 'Mine', tag: 'view' });
        await updateEntry(entry.id, { title: 'Edited on the train, offline' });

        const result = await pullChanges({
            fetch: syncServer([{ data: [remote({ id: entry.id, title: 'Server copy', client_updated_at: '2030-01-01T00:00:00.000Z' })], next_cursor: 'c1', has_more: false }]).fetch,
        });

        expect(result.skipped).toBe(1);
        expect((await db.entries.get(entry.id)).title).toBe('Edited on the train, offline');
    });

    it('removes entries deleted on another device, with their photos', async () => {
        await pullChanges({ fetch: syncServer([{ data: [remote({ photos: [{ id: '01K7C0000000000000000000P1', width: 400, height: 300, bytes: 10, created_at: '2026-10-09T13:06:00.000Z' }] })], next_cursor: 'c1', has_more: false }]).fetch });

        await pullChanges({ fetch: syncServer([{ data: [remote({ deleted_at: '2026-10-10T08:00:00.000Z' })], next_cursor: 'c2', has_more: false }]).fetch });

        expect(await db.entries.get(ID)).toBeUndefined();
        expect(await db.photos.count()).toBe(0);
    });

    it('starts again from the beginning if the server rejects the cursor', async () => {
        await setMeta('pullCursor', 'stale');
        const server = syncServer([{ status: 422, body: { message: 'The sync cursor is not valid.' } }, { data: [remote()], next_cursor: 'c1', has_more: false }]);

        await pullChanges({ fetch: server.fetch });

        expect(server.asked).toEqual(['stale', null]);
        expect(await getMeta('pullCursor')).toBe('c1');
    });

    it('flags a re-login on 401', async () => {
        const server = syncServer([{ status: 401, body: { message: 'Unauthenticated.' } }]);

        expect((await pullChanges({ fetch: server.fetch })).status).toBe('auth-required');
        expect(await getMeta('authRequired')).toBe(true);
    });
});

describe('photo downloads', () => {
    const photo = { id: '01K7C0000000000000000000P1', width: 1600, height: 1200, bytes: 2000, created_at: '2026-10-09T13:06:00.000Z' };

    it('records pulled photos without blobs, then fills thumbnails and full images on demand', async () => {
        const images = { [`/api/photos/${photo.id}/thumb`]: 'thumb-bytes', [`/api/photos/${photo.id}`]: 'full-bytes' };
        const server = syncServer([{ data: [remote({ photos: [photo] })], next_cursor: 'c1', has_more: false }], { images });

        await pullChanges({ fetch: server.fetch });
        expect(await db.photos.get(photo.id)).toMatchObject({ entryId: ID, blob: null, thumb: null, remote: true, syncStatus: 'synced' });
        expect(await db.outbox.count()).toBe(0);

        expect(await downloadThumbnails({ fetch: server.fetch })).toBe(1);
        expect(await (await db.photos.get(photo.id)).thumb.text()).toBe('thumb-bytes');

        expect(await (await fetchFullPhoto(photo.id, { fetch: server.fetch })).text()).toBe('full-bytes');
        expect(await (await db.photos.get(photo.id)).blob.text()).toBe('full-bytes');
    });
});
