/**
 * Blob URLs keyed by photo id, so live queries re-rendering a list do not
 * mint a fresh URL (and leak the old one) on every change.
 */
const urls = new Map();

export function photoUrl(photo, field = 'thumb') {
    const blob = photo?.[field] ?? photo?.blob;

    if (!blob) {
        return null;
    }

    const key = `${photo.id}:${field}`;

    if (!urls.has(key)) {
        urls.set(key, URL.createObjectURL(blob));
    }

    return urls.get(key);
}

export function blobUrl(key, blob) {
    if (!urls.has(key)) {
        urls.set(key, URL.createObjectURL(blob));
    }

    return urls.get(key);
}

export function revokeUrl(key) {
    if (urls.has(key)) {
        URL.revokeObjectURL(urls.get(key));
        urls.delete(key);
    }
}
