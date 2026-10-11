import { AREAS, LOOK_OUT, routeUrl, walkNote, WALKS } from '../data/walks';
import { localised } from './format';

/**
 * Turn a route's GeoJSON into an SVG path that fits a small box, keeping
 * its true proportions (longitude scaled by latitude), so each card shows
 * the real shape of the walk.
 */
export function routeSketch(geojson, width = 160, height = 96, pad = 6) {
    const lines = geojson.features.flatMap((feature) =>
        feature.geometry.type === 'MultiLineString' ? feature.geometry.coordinates : [feature.geometry.coordinates],
    );
    const points = lines.flat();
    const lats = points.map(([, lat]) => lat);
    const midLat = ((Math.min(...lats) + Math.max(...lats)) / 2) * (Math.PI / 180);
    const project = ([lng, lat]) => [lng * Math.cos(midLat), -lat];
    const projected = points.map(project);
    const xs = projected.map(([x]) => x);
    const ys = projected.map(([, y]) => y);
    const [minX, maxX, minY, maxY] = [Math.min(...xs), Math.max(...xs), Math.min(...ys), Math.max(...ys)];
    const scale = Math.min((width - pad * 2) / (maxX - minX || 1), (height - pad * 2) / (maxY - minY || 1));
    const offsetX = (width - (maxX - minX) * scale) / 2;
    const offsetY = (height - (maxY - minY) * scale) / 2;

    return lines
        .map((line) =>
            line
                .map(project)
                .map(([x, y], index) => `${index ? 'L' : 'M'}${((x - minX) * scale + offsetX).toFixed(1)} ${((y - minY) * scale + offsetY).toFixed(1)}`)
                .join(''),
        )
        .join('');
}

export function walkList() {
    return {
        sketches: {},
        walkNote: localised(walkNote),
        areas: Object.entries(AREAS).map(([key, area]) => ({
            key,
            ...area,
            walks: WALKS.filter((walk) => walk.area === key),
            lookOut: LOOK_OUT[key],
        })),

        async init() {
            for (const walk of WALKS) {
                try {
                    this.sketches[walk.id] = routeSketch(await (await fetch(routeUrl(walk.id))).json());
                } catch {
                    // No sketch for a route that failed to load
                }
            }
        },
    };
}
