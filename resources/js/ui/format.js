const dateTime = new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium', timeStyle: 'short' });
const relative = new Intl.RelativeTimeFormat('en-GB', { numeric: 'auto' });

export function formatDateTime(iso) {
    return iso ? dateTime.format(new Date(iso)) : '';
}

export function formatRelative(iso) {
    if (!iso) {
        return '';
    }

    const seconds = Math.round((Date.parse(iso) - Date.now()) / 1000);
    const units = [
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return relative.format(Math.round(seconds / size), unit);
        }
    }

    return 'just now';
}

export function formatCoords(entry) {
    if (entry?.lat === null || entry?.lat === undefined) {
        return null;
    }

    return `${entry.lat.toFixed(5)}, ${entry.lng.toFixed(5)}`;
}

export const SYNC_BADGES = {
    local: { label: 'On device', classes: 'bg-bracken-400/20 text-bracken-600' },
    syncing: { label: 'Syncing', classes: 'bg-slate-mist text-peat-800' },
    synced: { label: 'Synced', classes: 'bg-moss-500/15 text-moss-700' },
    failed: { label: 'Failed', classes: 'bg-red-100 text-red-800' },
};
