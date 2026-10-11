import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ROUTE_STYLE, tagColour } from './data/map-style';

const WALES = [
    [51.35, -5.35],
    [53.45, -2.65],
];

const { entries, routes, labels } = JSON.parse(document.getElementById('map-data').textContent);

// Key: what the dots and the dashed lines mean
const key = document.getElementById('map-key');
key.setAttribute('role', 'group');
key.setAttribute('aria-label', labels.key);
key.innerHTML = '<ul class="flex flex-wrap items-center gap-x-3 gap-y-1.5"></ul>';

for (const [tag, label] of Object.entries(labels.tags)) {
    const item = document.createElement('li');
    item.className = 'flex items-center gap-1.5';
    item.innerHTML = '<span class="size-2.5 rounded-full ring-2 ring-surface" aria-hidden="true"></span><span></span>';
    item.firstChild.style.background = tagColour(tag);
    item.lastChild.textContent = label;
    key.firstChild.append(item);
}

const routeItem = document.createElement('li');
routeItem.className = 'flex items-center gap-1.5';
routeItem.innerHTML = '<svg viewBox="0 0 24 6" class="h-1.5 w-6" aria-hidden="true"><path d="M1 3h22" stroke="#8a2d6b" stroke-width="3" stroke-dasharray="6 4" stroke-linecap="round" /></svg><span></span>';
routeItem.lastChild.textContent = labels.route;
key.firstChild.append(routeItem);

const map = L.map('map').fitBounds(WALES);

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
}).addTo(map);

for (const id of routes) {
    fetch(`/routes/${id}.geojson`)
        .then((response) => response.json())
        .then((geojson) => L.geoJSON(geojson, { style: ROUTE_STYLE }).addTo(map))
        .catch(() => {});
}

function popup(entry) {
    const wrapper = document.createElement('div');
    wrapper.className = 'max-w-64';

    const title = document.createElement('p');
    title.className = 'font-bold';
    title.textContent = entry.title;
    wrapper.append(title);

    const meta = document.createElement('p');
    meta.className = 'text-xs text-muted';
    meta.textContent = `${entry.tag}, ${new Date(entry.recorded_at).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' })}`;
    wrapper.append(meta);

    if (entry.notes) {
        const notes = document.createElement('p');
        notes.className = 'mt-1 text-sm';
        notes.textContent = entry.notes;
        wrapper.append(notes);
    }

    if (entry.photos.length) {
        const grid = document.createElement('div');
        grid.className = 'mt-2 grid grid-cols-2 gap-1';

        for (const photo of entry.photos) {
            const link = document.createElement('a');
            link.href = photo.full;
            link.target = '_blank';
            link.rel = 'noopener';

            const image = document.createElement('img');
            image.src = photo.thumb;
            image.alt = '';
            image.className = 'aspect-square w-full rounded object-cover';
            image.loading = 'lazy';

            link.append(image);
            grid.append(link);
        }

        wrapper.append(grid);
    }

    return wrapper;
}

const markers = {};
const located = entries.filter((entry) => entry.lat !== null && entry.lng !== null);

for (const entry of located) {
    markers[entry.id] = L.circleMarker([entry.lat, entry.lng], {
        radius: 9,
        color: '#fff',
        weight: 2,
        fillColor: tagColour(entry.tag),
        fillOpacity: 0.95,
    })
        .bindPopup(() => popup(entry), { maxWidth: 280 })
        .addTo(map);
}

if (located.length) {
    map.fitBounds(L.latLngBounds(located.map((entry) => [entry.lat, entry.lng])), { padding: [40, 40], maxZoom: 14 });
}

document.getElementById('entry-list')?.addEventListener('click', (event) => {
    const marker = markers[event.target.closest('[data-entry]')?.dataset.entry];

    if (marker) {
        // On phones the map sits above the list, so bring it into view first
        document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'start' });
        map.setView(marker.getLatLng(), Math.max(map.getZoom(), 14));
        marker.openPopup();
    }
});
