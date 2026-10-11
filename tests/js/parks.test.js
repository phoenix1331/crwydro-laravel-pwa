import { readFileSync } from 'node:fs';
import { beforeAll, describe, expect, it } from 'vitest';
import { setLocale } from '../../resources/js/i18n';
import { parkAt, parkName, useParks } from '../../resources/js/data/parks';

beforeAll(() => {
    useParks(JSON.parse(readFileSync(new URL('../../public/parks/national-parks.geojson', import.meta.url))));
});

describe('parkAt', () => {
    it.each([
        ['Yr Wyddfa summit', 53.06853, -4.07619, 'eryri'],
        ['Cadair Idris', 52.69941, -3.90889, 'eryri'],
        ['Pen y Fan', 51.88369, -3.43664, 'bannau-brycheiniog'],
        ['St Davids Head', 51.9045, -5.3104, 'arfordir-penfro'],
    ])('places %s in the right park', (_, lat, lng, id) => {
        expect(parkAt(lat, lng)?.id).toBe(id);
    });

    it.each([
        ['Cardiff', 51.4816, -3.1791],
        ['Aberystwyth', 52.4153, -4.0829],
    ])('finds no park at %s', (_, lat, lng) => {
        expect(parkAt(lat, lng)).toBeNull();
    });

    it('returns null without a location', () => {
        expect(parkAt(null, null)).toBeNull();
    });

    it('names the park in the chosen language', () => {
        const park = parkAt(53.06853, -4.07619);

        setLocale('cy');
        expect(parkName(park)).toBe('Eryri');
        setLocale('en');
        expect(parkName(park)).toBe('Eryri (Snowdonia)');
    });
});
