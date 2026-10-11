import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

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

const { entries, routes } = JSON.parse(document.getElementById('map-data').textContent);

const map = L.map('map').fitBounds(WALES);

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
}).addTo(map);

for (const id of routes) {
    fetch(`/routes/${id}.geojson`)
        .then((response) => response.json())
        .then((geojson) => L.geoJSON(geojson, { style: { color: '#2f4c25', weight: 4, opacity: 0.7 } }).addTo(map))
        .catch(() => {});
}

function popup(entry) {
    const wrapper = document.createElement('div');
    wrapper.className = 'max-w-64';

    const title = document.createElement('p');
    title.className = 'font-semibold';
    title.textContent = entry.title;
    wrapper.append(title);

    const meta = document.createElement('p');
    meta.className = 'text-xs text-peat-600';
    meta.textContent = `${entry.tag} · ${new Date(entry.recorded_at).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' })}`;
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
        fillColor: TAG_COLOURS[entry.tag] ?? TAG_COLOURS.other,
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
        map.setView(marker.getLatLng(), Math.max(map.getZoom(), 14));
        marker.openPopup();
    }
});
