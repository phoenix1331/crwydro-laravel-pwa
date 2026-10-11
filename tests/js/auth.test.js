import { describe, expect, it } from 'vitest';
import { db, getMeta, setMeta } from '../../resources/js/data/db';
import { authState, login } from '../../resources/js/data/auth';
import { createEntry } from '../../resources/js/data/entries';

const respond = (status, body) => async () => new Response(JSON.stringify(body), { status });

describe('login', () => {
    it('stores the token and user and clears a pending re-login', async () => {
        await setMeta('authRequired', true);

        const user = await login('walker@crwydro.test', 'password', {
            fetch: respond(200, { token: '1|abc', user: { id: 1, name: 'Demo Walker', email: 'walker@crwydro.test' } }),
        });

        expect(user.name).toBe('Demo Walker');
        expect(await getMeta('token')).toBe('1|abc');
        expect(await authState()).toMatchObject({ loggedIn: true, authRequired: false, user: { id: 1 } });
    });

    it('surfaces the validation message on bad credentials', async () => {
        await expect(
            login('walker@crwydro.test', 'nope', {
                fetch: respond(422, { message: 'x', errors: { email: ['These credentials do not match our records.'] } }),
            }),
        ).rejects.toThrow('These credentials do not match our records.');

        expect(await getMeta('token')).toBeNull();
    });

    it('explains that login needs signal, and leaves local data alone', async () => {
        const entry = await createEntry({ title: 'Pabi', tag: 'flower' });

        await expect(
            login('walker@crwydro.test', 'password', {
                fetch: async () => {
                    throw new TypeError('Failed to fetch');
                },
            }),
        ).rejects.toThrow('needs a signal');

        expect(await db.entries.get(entry.id)).toBeTruthy();
        expect(await db.outbox.count()).toBe(1);
    });
});
