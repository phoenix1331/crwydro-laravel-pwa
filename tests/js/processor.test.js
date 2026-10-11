import { beforeEach, describe, expect, it } from 'vitest';
import { db, getMeta, setMeta } from '../../resources/js/data/db';
import { createEntry, deleteEntry, updateEntry } from '../../resources/js/data/entries';
import { queuePhotoPut } from '../../resources/js/data/outbox';
import { backoffDelay } from '../../resources/js/sync/backoff';
import { nextRetryAt, processOutbox, retryFailed } from '../../resources/js/sync/processor';

/**
 * A fake server: routes are matched by "METHOD /path" prefix and return
 * [status, body], or throw to simulate the connection dropping.
 */
function fakeServer(routes = {}) {
    const calls = [];

    const fetch = async (url, init) => {
        const key = `${init.method} ${url}`;
        calls.push({ key, init });

        const handler = Object.entries(routes).find(([pattern]) => key.startsWith(pattern))?.[1];
        const result = typeof handler === 'function' ? await handler(init) : handler;

        if (result instanceof Error) {
            throw result;
        }

        const [status, body] = result ?? (key === 'GET /api/ping' ? [200, { ok: true }] : [200, { data: {} }]);

        return new Response(body === null ? null : JSON.stringify(body), { status });
    };

    return { fetch, calls, sent: () => calls.map((call) => call.key).filter((key) => key !== 'GET /api/ping') };
}

const clock = { now: Date.parse('2026-10-11T12:00:00.000Z') };
const options = (server) => ({ fetch: server.fetch, now: () => clock.now, random: () => 0.5 });

beforeEach(async () => {
    clock.now = Date.parse('2026-10-11T12:00:00.000Z');
    await setMeta('token', 'test-token');
});

describe('processOutbox', () => {
    it('does nothing without a token', async () => {
        await db.meta.delete('token');
        await createEntry({ title: 'Pabi', tag: 'flower' });
        const server = fakeServer();

        expect((await processOutbox(options(server))).status).toBe('unauthenticated');
        expect(server.calls).toHaveLength(0);
    });

    it('sends nothing when the ping fails', async () => {
        await createEntry({ title: 'Pabi', tag: 'flower' });
        const server = fakeServer({ 'GET /api/ping': new TypeError('Failed to fetch') });

        expect((await processOutbox(options(server))).status).toBe('offline');
        expect(server.sent()).toEqual([]);
        expect(await db.outbox.count()).toBe(1);
    });

    it('sends the latest entry state with the bearer token and marks it synced', async () => {
        const entry = await createEntry({ title: 'Pabi', tag: 'flower', location: { lat: 52.68, lng: -3.89, accuracy: 8, altitude: null } });
        await updateEntry(entry.id, { title: 'Pabi Cymreig' });
        const server = fakeServer();

        const result = await processOutbox(options(server));

        expect(result).toMatchObject({ status: 'done', sent: 1 });
        const put = server.calls.find((call) => call.key === `PUT /api/entries/${entry.id}`);
        expect(put.init.headers.Authorization).toBe('Bearer test-token');
        expect(JSON.parse(put.init.body)).toMatchObject({ title: 'Pabi Cymreig', tag: 'flower', lat: 52.68, accuracy_m: 8 });
        expect(await db.outbox.count()).toBe(0);
        expect((await db.entries.get(entry.id)).syncStatus).toBe('synced');
        expect(await getMeta('lastSyncAt')).toBe('2026-10-11T12:00:00.000Z');
    });

    it('processes rows in seq order', async () => {
        const a = await createEntry({ title: 'A', tag: 'flower' });
        const b = await createEntry({ title: 'B', tag: 'bird' });
        await db.photos.add({ id: '01K7A3RB4C6D8E0F2G4H6J8K0M', entryId: a.id, blob: new Blob(['x']), syncStatus: 'local' });
        await queuePhotoPut('01K7A3RB4C6D8E0F2G4H6J8K0M', a.id);
        await deleteEntry(b.id);
        const server = fakeServer({ 'DELETE ': [204, null] });

        await processOutbox(options(server));

        expect(server.sent()).toEqual([
            `PUT /api/entries/${a.id}`,
            `PUT /api/entries/${b.id}`,
            `PUT /api/entries/${a.id}/photos/01K7A3RB4C6D8E0F2G4H6J8K0M`,
            `DELETE /api/entries/${b.id}`,
        ]);
    });

    it('purges an entry from the device once its delete is confirmed', async () => {
        const entry = await createEntry({ title: 'Mistake', tag: 'other' });
        await deleteEntry(entry.id);

        await processOutbox(options(fakeServer({ 'DELETE ': [204, null] })));

        expect(await db.entries.get(entry.id)).toBeUndefined();
    });

    it('keeps the row and backs off on a server error', async () => {
        const entry = await createEntry({ title: 'Pabi', tag: 'flower' });
        const server = fakeServer({ 'PUT /api/entries': [503, { message: 'Down for maintenance' }] });

        const result = await processOutbox(options(server));

        const [row] = await db.outbox.toArray();
        expect(result).toMatchObject({ status: 'done', deferred: 1 });
        expect(row).toMatchObject({ attempts: 1, failed: false, lastError: 'Down for maintenance' });
        expect(row.nextAttemptAt).toBe(clock.now + backoffDelay(1, () => 0.5));
        expect((await db.entries.get(entry.id)).syncStatus).toBe('local');
        expect(await nextRetryAt()).toBe(row.nextAttemptAt);
    });

    it('skips rows still in backoff but lets other entries through', async () => {
        const waiting = await createEntry({ title: 'Waiting', tag: 'flower' });
        const other = await createEntry({ title: 'Other', tag: 'bird' });
        const [row] = await db.outbox.where('entryId').equals(waiting.id).toArray();
        await db.outbox.update(row.seq, { attempts: 1, nextAttemptAt: clock.now + 60_000 });
        const server = fakeServer();

        await processOutbox(options(server));

        expect(server.sent()).toEqual([`PUT /api/entries/${other.id}`]);

        clock.now += 60_000;
        await processOutbox(options(server));
        expect(server.sent()).toContain(`PUT /api/entries/${waiting.id}`);
    });

    it('treats 409 as retryable, not failed', async () => {
        await createEntry({ title: 'Pabi', tag: 'flower' });

        await processOutbox(options(fakeServer({ 'PUT ': [409, { message: 'Conflict' }] })));

        expect((await db.outbox.toArray())[0]).toMatchObject({ failed: false, attempts: 1 });
    });

    it('marks a rejected entry failed and holds back its later rows', async () => {
        const entry = await createEntry({ title: 'Bad', tag: 'flower' });
        await deleteEntry(entry.id);
        const server = fakeServer({ 'PUT ': [422, { message: 'Invalid', errors: { title: ['The title is too long.'] } }] });

        const result = await processOutbox(options(server));

        expect(result.failed).toBe(1);
        expect(server.sent()).toEqual([`PUT /api/entries/${entry.id}`]);
        expect(await db.entries.get(entry.id)).toMatchObject({ syncStatus: 'failed', syncError: 'The title is too long.' });

        await retryFailed();
        expect((await db.outbox.toArray()).every((row) => !row.failed)).toBe(true);
        expect((await db.entries.get(entry.id)).syncStatus).toBe('local');
    });

    it('stops on 401 and flags that the walker must log in again, keeping the outbox', async () => {
        await createEntry({ title: 'A', tag: 'flower' });
        await createEntry({ title: 'B', tag: 'bird' });
        const server = fakeServer({ 'PUT ': [401, { message: 'Unauthenticated.' }] });

        expect((await processOutbox(options(server))).status).toBe('auth-required');
        expect(server.sent()).toHaveLength(1);
        expect(await db.outbox.count()).toBe(2);
        expect(await getMeta('authRequired')).toBe(true);
        expect((await db.entries.toArray()).every((entry) => entry.syncStatus === 'local')).toBe(true);

        expect((await processOutbox(options(server))).status).toBe('auth-required');
        expect(server.sent()).toHaveLength(1);
    });

    it('stops the run when the connection drops mid-sync and resumes later without duplicates', async () => {
        const a = await createEntry({ title: 'A', tag: 'flower' });
        const b = await createEntry({ title: 'B', tag: 'bird' });
        let drop = true;
        const server = fakeServer({
            [`PUT /api/entries/${b.id}`]: () => (drop ? new TypeError('Failed to fetch') : [201, { data: {} }]),
        });

        expect((await processOutbox(options(server))).status).toBe('interrupted');
        expect((await db.entries.get(a.id)).syncStatus).toBe('synced');
        expect((await db.outbox.toArray())[0]).toMatchObject({ refId: b.id, attempts: 1 });

        drop = false;
        clock.now += 10 * 60_000;
        await processOutbox(options(server));

        expect(server.sent().filter((key) => key === `PUT /api/entries/${a.id}`)).toHaveLength(1);
        expect(await db.outbox.count()).toBe(0);
    });

    it('keeps the row when the entry is edited while its request is in flight', async () => {
        const entry = await createEntry({ title: 'First', tag: 'flower' });
        const server = fakeServer({
            'PUT ': async () => {
                await updateEntry(entry.id, { title: 'Edited mid-flight' });

                return [201, { data: {} }];
            },
        });

        await processOutbox(options(server));

        const [row] = await db.outbox.toArray();
        expect(row.rev).toBe(2);
        expect((await db.entries.get(entry.id)).syncStatus).toBe('local');
    });
});

describe('backoffDelay', () => {
    it('doubles from five seconds and caps at fifteen minutes', () => {
        const mid = () => 0.5;

        expect(backoffDelay(1, mid)).toBe(5_000);
        expect(backoffDelay(2, mid)).toBe(10_000);
        expect(backoffDelay(4, mid)).toBe(40_000);
        expect(backoffDelay(20, mid)).toBe(15 * 60_000);
    });
});

describe('photo uploads', () => {
    const PHOTO_ID = '01K7A3RB4C6D8E0F2G4H6J8K0M';

    async function entryWithPhoto() {
        const entry = await createEntry({ title: 'Llyn Cau', tag: 'view' });
        await db.photos.add({ id: PHOTO_ID, entryId: entry.id, blob: new Blob(['jpeg-bytes'], { type: 'image/jpeg' }), syncStatus: 'local', createdAt: '' });
        await queuePhotoPut(PHOTO_ID, entry.id);

        return entry;
    }

    it('uploads each photo as a raw jpeg body and marks it synced', async () => {
        const entry = await entryWithPhoto();
        const server = fakeServer();

        await processOutbox(options(server));

        const upload = server.calls.find((call) => call.key.endsWith(`/photos/${PHOTO_ID}`));
        expect(upload.init.headers['Content-Type']).toBe('image/jpeg');
        expect(await upload.init.body.text()).toBe('jpeg-bytes');
        expect((await db.photos.get(PHOTO_ID)).syncStatus).toBe('synced');
        expect((await db.entries.get(entry.id)).syncStatus).toBe('synced');
    });

    it('does not let a failing photo hold back a later edit to the entry text', async () => {
        const entry = await entryWithPhoto();
        const server = fakeServer({ [`PUT /api/entries/${entry.id}/photos`]: [503, { message: 'Busy' }] });

        await processOutbox(options(server));
        await updateEntry(entry.id, { title: 'Llyn Cau, fixed' });

        await processOutbox(options(server));

        expect(server.sent().filter((key) => key === `PUT /api/entries/${entry.id}`)).toHaveLength(2);
        const rows = await db.outbox.toArray();
        expect(rows.map((row) => row.kind)).toEqual(['photo.put']);
    });

    it('marks the entry failed with the reason when a photo is rejected, but still sends its text', async () => {
        const entry = await entryWithPhoto();
        await updateEntry(entry.id, { notes: 'later edit' });
        const server = fakeServer({ [`PUT /api/entries/${entry.id}/photos`]: [422, { message: 'x', errors: { photo: ['The photo must be a JPEG image.'] } }] });

        await processOutbox(options(server));

        expect(server.sent()).toEqual([`PUT /api/entries/${entry.id}`, `PUT /api/entries/${entry.id}/photos/${PHOTO_ID}`]);
        expect(await db.entries.get(entry.id)).toMatchObject({ syncStatus: 'failed', syncError: 'The photo must be a JPEG image.' });
    });

    it('holds photos back while the entry itself is waiting to be created', async () => {
        await entryWithPhoto();
        const server = fakeServer({ 'PUT /api/entries/': [503, { message: 'Busy' }] });

        await processOutbox(options(server));

        expect(server.sent()).toHaveLength(1);
        expect(server.sent()[0]).not.toContain('/photos/');
    });
});
