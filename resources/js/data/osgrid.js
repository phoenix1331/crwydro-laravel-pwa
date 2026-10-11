/**
 * WGS84 latitude/longitude to an Ordnance Survey National Grid reference
 * (e.g. "SH 6095 5436"), the form walkers use on OS Explorer maps.
 * Helmert transform to OSGB36, then Transverse Mercator onto the grid.
 * Accurate to a few metres, which suits phone GPS.
 */
const RAD = Math.PI / 180;

function toCartesian(lat, lng, a, b, height = 0) {
    const e2 = 1 - (b * b) / (a * a);
    const sinLat = Math.sin(lat * RAD);
    const nu = a / Math.sqrt(1 - e2 * sinLat * sinLat);

    return [
        (nu + height) * Math.cos(lat * RAD) * Math.cos(lng * RAD),
        (nu + height) * Math.cos(lat * RAD) * Math.sin(lng * RAD),
        ((1 - e2) * nu + height) * sinLat,
    ];
}

function fromCartesian([x, y, z], a, b) {
    const e2 = 1 - (b * b) / (a * a);
    const p = Math.sqrt(x * x + y * y);
    let lat = Math.atan2(z, p * (1 - e2));

    for (let i = 0; i < 10; i++) {
        const nu = a / Math.sqrt(1 - e2 * Math.sin(lat) ** 2);
        lat = Math.atan2(z + e2 * nu * Math.sin(lat), p);
    }

    return [lat / RAD, Math.atan2(y, x) / RAD];
}

// WGS84 to OSGB36 (Ordnance Survey's published 7-parameter Helmert)
function wgs84ToOsgb36(lat, lng) {
    const [x1, y1, z1] = toCartesian(lat, lng, 6378137.0, 6356752.314245);
    const tx = -446.448;
    const ty = 125.157;
    const tz = -542.06;
    const s = 20.4894e-6;
    const rx = (-0.1502 / 3600) * RAD;
    const ry = (-0.247 / 3600) * RAD;
    const rz = (-0.8421 / 3600) * RAD;

    const x2 = tx + (1 + s) * x1 - rz * y1 + ry * z1;
    const y2 = ty + rz * x1 + (1 + s) * y1 - rx * z1;
    const z2 = tz - ry * x1 + rx * y1 + (1 + s) * z1;

    return fromCartesian([x2, y2, z2], 6377563.396, 6356256.909);
}

function toEastingNorthing(lat, lng) {
    const a = 6377563.396;
    const b = 6356256.909;
    const F0 = 0.9996012717;
    const lat0 = 49 * RAD;
    const lng0 = -2 * RAD;
    const N0 = -100000;
    const E0 = 400000;
    const e2 = 1 - (b * b) / (a * a);
    const n = (a - b) / (a + b);
    const phi = lat * RAD;
    const lambda = lng * RAD;

    const sinPhi = Math.sin(phi);
    const cosPhi = Math.cos(phi);
    const nu = (a * F0) / Math.sqrt(1 - e2 * sinPhi * sinPhi);
    const rho = (a * F0 * (1 - e2)) / (1 - e2 * sinPhi * sinPhi) ** 1.5;
    const eta2 = nu / rho - 1;

    const dPhi = phi - lat0;
    const sPhi = phi + lat0;
    const M =
        b *
        F0 *
        ((1 + n + (5 / 4) * n ** 2 + (5 / 4) * n ** 3) * dPhi -
            (3 * n + 3 * n ** 2 + (21 / 8) * n ** 3) * Math.sin(dPhi) * Math.cos(sPhi) +
            ((15 / 8) * n ** 2 + (15 / 8) * n ** 3) * Math.sin(2 * dPhi) * Math.cos(2 * sPhi) -
            (35 / 24) * n ** 3 * Math.sin(3 * dPhi) * Math.cos(3 * sPhi));

    const tan2 = Math.tan(phi) ** 2;
    const I = M + N0;
    const II = (nu / 2) * sinPhi * cosPhi;
    const III = (nu / 24) * sinPhi * cosPhi ** 3 * (5 - tan2 + 9 * eta2);
    const IIIA = (nu / 720) * sinPhi * cosPhi ** 5 * (61 - 58 * tan2 + tan2 ** 2);
    const IV = nu * cosPhi;
    const V = (nu / 6) * cosPhi ** 3 * (nu / rho - tan2);
    const VI = (nu / 120) * cosPhi ** 5 * (5 - 18 * tan2 + tan2 ** 2 + 14 * eta2 - 58 * tan2 * eta2);
    const dL = lambda - lng0;

    return [
        E0 + IV * dL + V * dL ** 3 + VI * dL ** 5,
        I + II * dL ** 2 + III * dL ** 4 + IIIA * dL ** 6,
    ];
}

const LETTERS = 'ABCDEFGHJKLMNOPQRSTUVWXYZ';

/**
 * @param {number} digits figures per axis: 3 (100 m), 4 (10 m) or 5 (1 m)
 */
export function osGridRef(lat, lng, digits = 4) {
    if (lat === null || lng === null || lat === undefined || lng === undefined) {
        return null;
    }

    const [osLat, osLng] = wgs84ToOsgb36(lat, lng);
    const [easting, northing] = toEastingNorthing(osLat, osLng);

    if (easting < 0 || easting >= 700000 || northing < 0 || northing >= 1300000) {
        return null;
    }

    const e100k = Math.floor(easting / 100000);
    const n100k = Math.floor(northing / 100000);
    let l1 = 19 - n100k - ((19 - n100k) % 5) + Math.floor((e100k + 10) / 5);
    let l2 = (((19 - n100k) * 5) % 25) + (e100k % 5);
    const letters = LETTERS[l1] + LETTERS[l2];

    const scale = 10 ** (5 - digits);
    const e = String(Math.floor((easting % 100000) / scale)).padStart(digits, '0');
    const n = String(Math.floor((northing % 100000) / scale)).padStart(digits, '0');

    return `${letters} ${e} ${n}`;
}
