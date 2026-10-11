import { liveQuery } from 'dexie';
import { listEntries } from '../data/entries';
import { TAGS, tagLabel } from '../data/tags';
import { ROUTE_STYLE, tagColour } from '../data/map-style';
import { routeUrl, walkById, WALKS } from '../data/walks';

const WALES = [
    [51.35, -5.35],
    [53.45, -2.65],
];

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
        ready: null,
        positioned: false,
        leaflet: null,
        markers: null,
        routes: {},
        subscription: null,
        focusedWalk: null,
        walks: WALKS,
        tags: TAGS,

        tagColour,

        async show(route, walkId) {
            if (route !== 'map') {
                return;
            }

            // Route changes can call this twice before the map exists; both wait for one creation
            this.ready ??= this.create();
            await this.ready;

            // The view was hidden until now: wait until it has a size, or Leaflet fits to a 0×0 box
            // (zooming to the whole world or to street level)
            await this.whenVisible();

            this.map.invalidateSize();

            // The initial Wales view was set while hidden; redo it now the map can be measured
            if (!this.positioned && !walkId) {
                this.map.fitBounds(WALES);
            }

            this.positioned = true;
            this.focus(walkId);
        },

        whenVisible() {
            return new Promise((resolve) => {
                const check = (attempts) => {
                    if (this.$refs.map.clientWidth > 0 || attempts > 60) {
                        resolve();

                        return;
                    }

                    requestAnimationFrame(() => check(attempts + 1));
                };

                check(0);
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
                    .geoJSON(geojson, { style: ROUTE_STYLE })
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
                    fillColor: tagColour(entry.tag),
                    fillOpacity: 0.95,
                })
                    .bindPopup(popup)
                    .addTo(this.markers);
            }
        },

        /**
         * "Show on map" for a walk always fits that walk, even if it was shown
         * before. Opening the map without a walk keeps whatever view the
         * walker left it at.
         */
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
