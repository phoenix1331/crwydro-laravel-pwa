import cy from './lang/cy.json';
import en from './lang/en.json';

/**
 * Client-side translations, bundled from lang/{en,cy}/app.php by
 * `php artisan crwydro:export-lang` so they work offline. The choice is
 * remembered per device and mirrored to a cookie for the server pages.
 */
const MESSAGES = { en, cy };
const STORAGE_KEY = 'crwydro.locale';

function initialLocale() {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);

        if (saved in MESSAGES) {
            return saved;
        }
    } catch {
        // Storage can be unavailable (private mode); fall back to the browser language
    }

    return (globalThis.navigator?.language ?? '').toLowerCase().startsWith('cy') ? 'cy' : 'en';
}

let current = initialLocale();

export function setLocale(value) {
    if (value in MESSAGES) {
        current = value;
    }
}

export const LOCALES = Object.keys(MESSAGES);

export function locale() {
    return current;
}

/** BCP 47 tag for Intl formatting and the html lang attribute */
export function localeTag(value = current) {
    return value === 'cy' ? 'cy' : 'en-GB';
}

export function t(key, params = {}) {
    let message = MESSAGES[current][key] ?? MESSAGES.en[key] ?? key;

    for (const [name, value] of Object.entries(params)) {
        message = message.replaceAll(`:${name}`, value);
    }

    return message;
}
