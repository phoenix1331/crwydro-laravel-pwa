import { liveQuery } from 'dexie';
import { listEntries } from '../data/entries';
import { tagLabel } from '../data/tags';
import { routeUrl, walkById, WALKS } from '../data/walks';

const WALES = [
    [51.35, -5.35],
    [53.45, -2.65],
];

const TAG_COLOURS = {
    flower: '#c8741f',
    bird: '#2f6db5',
    fungus: '#7a4f2a',
    insect: '#8a3fa0',
    view: '#3e6330',
    other: '#4a5a46',
};

export const TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

/**
 * Device map: featured walk lines and every local entry with a location.
 * Leaflet is loaded on first visit so it never weighs down the journal views.
 * Tiles are cached by the service worker as they are viewed, so areas seen
 * with signal still have a base map offline; routes and markers always work.
 */
export function deviceMap() {
    return {
        map: null,
        leaflet: null,
        markers: null,
        routes: {},
        subscription: null,
        focusedWalk: null,

        async show(route, walkId) {
            if (route !== 'map') {
                return;
            }

            if (!this.map) {
                await this.create();
            }

            // Leaflet measures its container, which was hidden until now
            this.$nextTick(() => {
                this.map.invalidateSize();
                this.focus(walkId);
            });
        },

        async create() {
            const L = (await import('leaflet')).default;
            await import('leaflet/dist/leaflet.css');

            this.leaflet = L;
            this.map = L.map(this.$refs.map, { zoomControl: false }).fitBounds(WALES);
            L.control.zoom({ position: 'topright' }).addTo(this.map);

            L.tileLayer(TILE_URL, {
                maxZoom: 17,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }).addTo(this.map);

            await Promise.all(WALKS.map((walk) => this.addRoute(walk)));

            this.markers = L.layerGroup().addTo(this.map);
            this.subscription = liveQuery(() => listEntries()).subscribe({
                next: (entries) => this.drawEntries(entries),
                error: (error) => console.error(error),
            });
        },

        async addRoute(walk) {
            try {
                const geojson = await (await fetch(routeUrl(walk.id))).json();

                this.routes[walk.id] = this.leaflet
                    .geoJSON(geojson, { style: { color: '#2f4c25', weight: 4, opacity: 0.75 } })
                    .bindTooltip(`${walk.cy} (${walk.en})`)
                    .addTo(this.map);
            } catch {
                // A route that failed to load just isn't drawn
            }
        },

        drawEntries(entries) {
            const L = this.leaflet;

            this.markers.clearLayers();

            for (const entry of entries.filter((item) => item.lat !== null)) {
                const popup = document.createElement('a');
                popup.href = `#/entries/${entry.id}`;
                popup.className = 'font-bold';
                popup.textContent = `${entry.title} (${tagLabel(entry.tag).toLowerCase()})`;

                L.circleMarker([entry.lat, entry.lng], {
                    radius: 9,
                    color: '#fff',
                    weight: 2,
                    fillColor: TAG_COLOURS[entry.tag] ?? TAG_COLOURS.other,
                    fillOpacity: 0.95,
                })
                    .bindPopup(popup)
                    .addTo(this.markers);
            }
        },

        focus(walkId) {
            this.focusedWalk = walkById(walkId);

            if (this.focusedWalk && this.routes[walkId]) {
                this.map.fitBounds(this.routes[walkId].getBounds(), { padding: [24, 24] });
            }
        },

        locate() {
            this.map.locate({ setView: true, maxZoom: 15, enableHighAccuracy: true });
        },
    };
}
