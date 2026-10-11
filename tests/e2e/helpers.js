import { expect } from '@playwright/test';

export const WALKER = { email: 'walker@crwydro.test', password: 'password' };

/** A tag unique to this run, so tests never trip over earlier data. */
export const run = `e2e-${Date.now().toString(36)}`;

export async function waitForServiceWorker(page) {
    await page.goto('/');
    await page.evaluate(async () => {
        await navigator.serviceWorker.ready;
    });
    await page.reload();
    await page.waitForFunction(() => Boolean(navigator.serviceWorker.controller));
}

export async function logIn(page) {
    await page.goto('/#/login');
    await page.getByLabel('Email').fill(WALKER.email);
    await page.getByLabel('Password').fill(WALKER.password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await page.waitForURL('**/#/sync');
    await page.waitForFunction(() => !Alpine.store('sync').running);
}

export async function createEntry(page, title) {
    await page.goto('/#/entries/new');
    await page.locator('#entry-title').fill(title);
    await page.getByRole('button', { name: 'Save entry' }).click();
    await page.waitForURL(/#\/$/);
}

export async function syncNow(page) {
    await page.waitForFunction(() => !Alpine.store('sync').running);
    await page.evaluate(() => Alpine.store('sync').trigger('manual'));
    await page.waitForFunction(() => !Alpine.store('sync').running);
}

export async function unsynced(page) {
    return page.evaluate(() => Alpine.store('sync').unsynced);
}

/** Every entry on the server whose title contains `needle`, read through the API as a second device would. */
let apiToken = null;

// One API login per run: /api/login is rate limited to 10 a minute
async function token(request) {
    if (!apiToken) {
        const login = await request.post('/api/login', {
            data: { ...WALKER, device_name: 'e2e' },
            headers: { Accept: 'application/json' },
        });
        expect(login.ok(), `API login failed with ${login.status()}`).toBeTruthy();
        apiToken = (await login.json()).token;
    }

    return apiToken;
}

export async function serverEntries(request, needle) {
    const bearer = await token(request);

    const entries = [];
    let cursor = null;

    for (;;) {
        const response = await request.get(`/api/sync${cursor ? `?since=${encodeURIComponent(cursor)}` : ''}`, {
            headers: { Accept: 'application/json', Authorization: `Bearer ${bearer}` },
        });
        const page = await response.json();
        entries.push(...page.data);
        cursor = page.next_cursor;

        if (!page.has_more) {
            break;
        }
    }

    return entries.filter((entry) => entry.title.includes(needle) && !entry.deleted_at);
}
