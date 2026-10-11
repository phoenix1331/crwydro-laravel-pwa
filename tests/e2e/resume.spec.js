import { expect, test } from '@playwright/test';
import { createEntry, logIn, run, serverEntries, syncNow, unsynced, waitForServiceWorker } from './helpers';

test('a connection that drops mid-sync loses nothing, duplicates nothing, and resumes', async ({ page, context, request }) => {
    await waitForServiceWorker(page);
    await logIn(page);

    await context.setOffline(true);
    await page.reload();
    const tag = `resume-${run}`;
    const titles = ['Barcud coch', 'Brân goesgoch', 'Pâl'].map((name) => `${name} ${tag}`);
    for (const title of titles) {
        await createEntry(page, title);
    }
    await expect.poll(() => unsynced(page)).toBe(3);

    // One bar of signal on Pen y Fan: the first upload gets through, then the connection drops
    let puts = 0;
    await page.route('**/api/entries/*', async (route) => {
        if (route.request().method() === 'PUT' && ++puts === 2) {
            await route.abort('internetdisconnected');

            return;
        }

        await route.continue();
    });

    // Coming back online also fires the app's own sync, which may race this one; either way the
    // drop must leave some entries waiting on the device and only the rest, once each, on the server
    await context.setOffline(false);
    await syncNow(page);

    const waiting = await unsynced(page);
    expect(waiting).toBeGreaterThanOrEqual(1);
    const partial = await serverEntries(request, tag);
    expect(partial).toHaveLength(3 - waiting);
    expect(new Set(partial.map((entry) => entry.id)).size).toBe(partial.length);

    // Signal returns properly: wait out the backoff for the interrupted row, then sync again
    await page.unroute('**/api/entries/*');
    const retryAt = await page.evaluate(() => Alpine.store('sync').retryAt ?? Date.now());
    await page.waitForTimeout(Math.max(0, retryAt - Date.now()) + 500);
    await syncNow(page);

    await expect.poll(() => unsynced(page)).toBe(0);

    const onServer = await serverEntries(request, tag);
    expect(onServer.map((entry) => entry.title).sort()).toEqual([...titles].sort());
});
