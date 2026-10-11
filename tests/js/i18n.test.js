import { describe, expect, it } from 'vitest';
import { setLocale, t } from '../../resources/js/i18n';
import { tagLabel } from '../../resources/js/data/tags';

describe('t', () => {
    it('translates, fills placeholders and falls back to english then the key', () => {
        setLocale('cy');
        expect(t('nav.entries')).toBe('Cofnodion');
        expect(t('status.count_on_device', { count: 3 })).toBe('3 ar y ddyfais hon');
        expect(tagLabel('bird')).toBe('Aderyn');
        expect(t('no.such.key')).toBe('no.such.key');

        setLocale('en');
        expect(t('nav.entries')).toBe('Entries');
        expect(tagLabel('bird')).toBe('Bird');
    });

    it('ignores an unknown locale', () => {
        setLocale('fr');
        expect(t('nav.map')).toBe('Map');
    });
});
