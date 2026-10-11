import { locale } from '../i18n';

/**
 * Which national park an entry falls in, worked out on the device from
 * precached boundaries (OpenStreetMap, simplified), so it works offline.
 */
export const PARKS_URL = '/parks/national-parks.geojson';

let parks = null;

export function useParks(geojson) {
    parks = geojson.features;
}

export async function loadParks(fetchImpl = globalThis.fetch.bind(globalThis)) {
    if (!parks) {
        useParks(await (await fetchImpl(PARKS_URL)).json());
    }

    return parks;
}

// Ray casting on one ring, treating longitude as x and latitude as y
function inRing([lng, lat], ring) {
    let inside = false;

    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const [xi, yi] = ring[i];
        const [xj, yj] = ring[j];

        if (yi > lat !== yj > lat && lng < ((xj - xi) * (lat - yi)) / (yj - yi) + xi) {
            inside = !inside;
        }
    }

    return inside;
}

// Inside the outer ring and outside every hole
function inPolygon(point, [outer, ...holes]) {
    return inRing(point, outer) && !holes.some((hole) => inRing(point, hole));
}

export function parkAt(lat, lng) {
    if (!parks || lat === null || lng === null || lat === undefined || lng === undefined) {
        return null;
    }

    const point = [lng, lat];

    for (const park of parks) {
        const { type, coordinates } = park.geometry;
        const polygons = type === 'MultiPolygon' ? coordinates : [coordinates];

        if (polygons.some((polygon) => inPolygon(point, polygon))) {
            return park.properties;
        }
    }

    return null;
}

// Welsh name first, English in brackets, as elsewhere in Crwydro
export function parkName(park) {
    if (!park) {
        return null;
    }

    return locale() === 'cy' ? park.cy : `${park.cy} (${park.en})`;
}
