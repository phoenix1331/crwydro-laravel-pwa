import { localeTag, t } from '../i18n';

export function formatDateTime(iso) {
    return iso ? new Intl.DateTimeFormat(localeTag(), { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '';
}

export function formatTime(timestamp) {
    return new Date(timestamp).toLocaleTimeString(localeTag(), { hour: '2-digit', minute: '2-digit' });
}

export function formatRelative(iso) {
    if (!iso) {
        return '';
    }

    const relative = new Intl.RelativeTimeFormat(localeTag(), { numeric: 'auto' });
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

    return t('time.just_now');
}

export function formatCoords(entry) {
    if (entry?.lat === null || entry?.lat === undefined) {
        return null;
    }

    return `${entry.lat.toFixed(5)}, ${entry.lng.toFixed(5)}`;
}

const BADGE_STYLES = {
    local: { classes: 'bg-warn-soft text-warn', text: 'text-warn', dot: 'bg-warn' },
    syncing: { classes: 'bg-sunk text-muted', text: 'text-muted', dot: 'bg-faint animate-pulse' },
    synced: { classes: 'bg-ok-soft text-ok', text: 'text-ok', dot: 'bg-ok' },
    failed: { classes: 'bg-danger-soft text-danger', text: 'text-danger', dot: 'bg-danger' },
};

export function syncBadge(status) {
    const key = status in BADGE_STYLES ? status : 'local';

    return { ...BADGE_STYLES[key], label: t(`status.${key}`) };
}

/**
 * Wrap a formatting helper for use as an Alpine component method: touching
 * the language store makes the calling expression re-render on a switch.
 */
export function localised(fn) {
    return function (...args) {
        this.$store?.i18n?.locale;

        return fn(...args);
    };
}
