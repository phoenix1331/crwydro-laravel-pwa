import { expect, test } from '@playwright/test';
import { createEntry, logIn, run, serverEntries, syncNow, unsynced, waitForServiceWorker } from './helpers';

test('an entry made offline reaches the server once signal returns', async ({ page, context, request }) => {
    await waitForServiceWorker(page);
    await logIn(page);

    await context.setOffline(true);
    await page.reload();
    await createEntry(page, `Pabi Cymreig offline ${run}`);

    await expect(page.locator('li', { hasText: `Pabi Cymreig offline ${run}` })).toContainText('On this device');
    await syncNow(page);
    await expect.poll(() => unsynced(page)).toBe(1);
    expect(await serverEntries(request, `Pabi Cymreig offline ${run}`)).toHaveLength(0);

    await context.setOffline(false);
    await syncNow(page);

    await expect.poll(() => unsynced(page)).toBe(0);
    await expect(page.locator('li', { hasText: `Pabi Cymreig offline ${run}` })).toContainText('Synced');

    const [entry] = await serverEntries(request, `Pabi Cymreig offline ${run}`);
    expect(entry).toMatchObject({ tag: 'flower', lat: 52.6935, lng: -3.901 });
});

test('the app shell, entries and walks all work with no network at all', async ({ page, context }) => {
    await waitForServiceWorker(page);
    await createEntry(page, `Shell check ${run}`);

    await context.setOffline(true);
    await page.reload();

    await expect(page.getByRole('heading', { name: 'Entries' })).toBeVisible();
    await expect(page.locator('li', { hasText: `Shell check ${run}` })).toBeVisible();

    await page.goto('/#/walks');
    await expect(page.getByRole('heading', { name: 'Walks' })).toBeVisible();
    expect(await page.evaluate(async () => (await fetch('/routes/clawdd-offa.geojson')).ok)).toBe(true);
});
