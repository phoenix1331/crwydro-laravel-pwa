import { db } from './db';

export const WARN_AT = 0.8;

/**
 * What this device is holding, from the browser's estimate plus Crwydro's own count.
 */
export async function storageReport() {
    const estimate = (await navigator.storage?.estimate?.()) ?? {};
    const persisted = (await navigator.storage?.persisted?.()) ?? false;

    let photoBytes = 0;
    let freeableBytes = 0;

    await db.photos.each((photo) => {
        const size = (photo.blob?.size ?? 0) + (photo.thumb?.size ?? 0);
        photoBytes += size;

        if (photo.syncStatus === 'synced' && photo.blob) {
            freeableBytes += photo.blob.size;
        }
    });

    const usage = estimate.usage ?? null;
    const quota = estimate.quota ?? null;

    return {
        usage,
        quota,
        ratio: usage !== null && quota ? usage / quota : null,
        persisted,
        supported: Boolean(navigator.storage?.persist),
        entries: await db.entries.filter((entry) => !entry.deletedAt).count(),
        photos: await db.photos.count(),
        photoBytes,
        freeableBytes,
    };
}

/**
 * Ask the browser not to evict Crwydro's data under storage pressure. Chromium
 * decides silently (installed and frequently used sites are granted); Safari
 * protects storage for apps added to the Home Screen.
 */
export async function requestPersistence() {
    if (!navigator.storage?.persist) {
        return false;
    }

    if (await navigator.storage.persisted()) {
        return true;
    }

    return navigator.storage.persist();
}

/**
 * Drop the full-size copy of photos the server already has. Thumbnails stay
 * for the list and detail views; the full image downloads again when opened.
 */
export async function freeSyncedPhotos() {
    let freed = 0;

    await db.transaction('rw', db.photos, async () => {
        await db.photos
            .filter((photo) => photo.syncStatus === 'synced' && Boolean(photo.blob))
            .modify((photo) => {
                freed += photo.blob.size;
                photo.blob = null;
                photo.remote = true;
            });
    });

    return freed;
}

export function formatBytes(bytes, locale = 'en-GB') {
    if (bytes === null || bytes === undefined) {
        return '';
    }

    const units = ['byte', 'kilobyte', 'megabyte', 'gigabyte'];
    let value = bytes;
    let unit = 0;

    while (value >= 1000 && unit < units.length - 1) {
        value /= 1000;
        unit++;
    }

    return new Intl.NumberFormat(locale, { style: 'unit', unit: units[unit], unitDisplay: 'short', maximumFractionDigits: value < 10 && unit > 0 ? 1 : 0 }).format(value);
}
