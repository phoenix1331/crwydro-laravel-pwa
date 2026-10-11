"""Fetch Welsh national park boundaries from OpenStreetMap (via Nominatim, simplified) into public/parks."""
import json
import sys
import time
import urllib.parse
import urllib.request

OUT = sys.argv[1]
UA = 'Crwydro/0.1 (open source learning project)'

# id, Welsh name, English name, Nominatim query
PARKS = [
    ('eryri', 'Eryri', 'Snowdonia', 'Eryri National Park'),
    ('bannau-brycheiniog', 'Bannau Brycheiniog', 'Brecon Beacons', 'Bannau Brycheiniog National Park'),
    ('arfordir-penfro', 'Arfordir Penfro', 'Pembrokeshire Coast', 'Pembrokeshire Coast National Park'),
]


def rounded(coordinates):
    if isinstance(coordinates[0], (int, float)):
        return [round(coordinates[0], 5), round(coordinates[1], 5)]
    return [rounded(item) for item in coordinates]


features = []
for park_id, cy, en, query in PARKS:
    params = urllib.parse.urlencode({'q': query, 'format': 'geojson', 'polygon_geojson': 1, 'polygon_threshold': 0.002, 'limit': 1})
    request = urllib.request.Request(f'https://nominatim.openstreetmap.org/search?{params}', headers={'User-Agent': UA})
    with urllib.request.urlopen(request, timeout=60) as response:
        found = json.load(response)['features'][0]
    relation = found['properties']['osm_id']
    features.append({
        'type': 'Feature',
        'properties': {
            'id': park_id,
            'cy': cy,
            'en': en,
            'osm_relation': relation,
            'source': f'https://www.openstreetmap.org/relation/{relation}',
            'attribution': '© OpenStreetMap contributors, ODbL 1.0',
        },
        'geometry': {'type': found['geometry']['type'], 'coordinates': rounded(found['geometry']['coordinates'])},
    })
    time.sleep(1.5)  # Nominatim usage policy: at most one request a second

with open(f'{OUT}/national-parks.geojson', 'w') as handle:
    json.dump({'type': 'FeatureCollection', 'features': features}, handle, separators=(',', ':'), ensure_ascii=False)
print(f'{len(features)} parks written')
