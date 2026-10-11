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
    local: { label: 'On this device', classes: 'bg-warn-soft text-warn' },
    syncing: { label: 'Syncing', classes: 'bg-sunk text-muted' },
    synced: { label: 'Synced', classes: 'bg-ok-soft text-ok' },
    failed: { label: 'Needs attention', classes: 'bg-danger-soft text-danger' },
};
