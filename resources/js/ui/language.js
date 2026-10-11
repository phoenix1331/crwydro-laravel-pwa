import Alpine from 'alpinejs';
import { LOCALES, locale, localeTag, setLocale, t } from '../i18n';

const STORAGE_KEY = 'crwydro.locale';

function apply(value) {
    setLocale(value);
    document.documentElement.lang = localeTag(value);
    document.cookie = `crwydro_locale=${value}; path=/; max-age=31536000; samesite=lax`;
}

/**
 * The walker's language choice: remembered on this device and mirrored to a
 * cookie so the server-rendered map and login pages match.
 */
export function languageStore() {
    return {
        locale: locale(),

        init() {
            apply(this.locale);
        },

        set(value) {
            if (!LOCALES.includes(value)) {
                return;
            }

            apply(value);
            this.locale = value;

            try {
                localStorage.setItem(STORAGE_KEY, value);
            } catch {
                // The cookie still carries the choice for this session
            }
        },

        toggle() {
            this.set(this.locale === 'cy' ? 'en' : 'cy');
        },
    };
}

/**
 * `$t('key')` in templates. Reading the store's locale makes every
 * expression that uses it re-render when the language changes.
 */
export function registerTranslationMagic() {
    Alpine.magic('t', () => (key, params) => {
        Alpine.store('i18n').locale;

        return t(key, params);
    });
}
