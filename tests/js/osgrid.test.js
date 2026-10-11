import { describe, expect, it } from 'vitest';
import { osGridRef } from '../../resources/js/data/osgrid';

describe('osGridRef', () => {
    it('places Yr Wyddfa summit at SH 609 543', () => {
        expect(osGridRef(53.06853, -4.07619, 3)).toBe('SH 609 543');
    });

    it('places Pen y Fan summit at SO 012 215', () => {
        expect(osGridRef(51.88369, -3.43664, 3)).toBe('SO 012 215');
    });

    it('places Cadair Idris (Penygadair) in SH', () => {
        expect(osGridRef(52.69941, -3.90889, 3)).toBe('SH 711 130');
    });

    it('gives an eight-figure reference by default', () => {
        expect(osGridRef(53.06853, -4.07619)).toMatch(/^SH \d{4} \d{4}$/);
    });

    it('returns null without a location or outside the grid', () => {
        expect(osGridRef(null, null)).toBeNull();
        expect(osGridRef(40.4, -3.7)).toBeNull();
    });
});
