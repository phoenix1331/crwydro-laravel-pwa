"""Fetch featured walk relations from Waymarked Trails (OpenStreetMap data) and write simplified GeoJSON."""
import json
import math
import sys
import urllib.request

OUT = sys.argv[1]
UA = 'Crwydro/0.1 (route data import for a learning project)'

# slug, OSM relation id, simplification tolerance in metres
ROUTES = [
    ('yr-wyddfa-llanberis', 4004181, 8),
    ('cwm-idwal', 19400332, 8),
    ('cadair-idris-minffordd', 1870230, 8),
    ('pen-y-fan-pont-ar-daf', 19690615, 8),
    ('arfordir-penfro', 77964, 120),
    ('clawdd-offa', 9649, 150),
    ('llwybr-glyndwr', 77256, 120),
]


def fetch(relation_id):
    request = urllib.request.Request(
        f'https://hiking.waymarkedtrails.org/api/v1/details/relation/{relation_id}',
        headers={'User-Agent': UA},
    )
    with urllib.request.urlopen(request, timeout=120) as response:
        return json.load(response)


def collect(node, lines):
    if isinstance(node, dict):
        geometry = node.get('geometry')
        if isinstance(geometry, dict) and geometry.get('type') == 'LineString':
            lines.append(geometry['coordinates'])
        for key, value in node.items():
            if key != 'geometry':
                collect(value, lines)
    elif isinstance(node, list):
        for item in node:
            collect(item, lines)


def to_wgs84(x, y):
    lng = x / 6378137.0 * 180.0 / math.pi
    lat = (2 * math.atan(math.exp(y / 6378137.0)) - math.pi / 2) * 180.0 / math.pi
    return [round(lng, 5), round(lat, 5)]


def perpendicular(point, start, end):
    if start == end:
        return math.dist(point, start)
    (x, y), (x1, y1), (x2, y2) = point, start, end
    return abs((y2 - y1) * x - (x2 - x1) * y + x2 * y1 - y2 * x1) / math.dist(start, end)


def simplify(points, tolerance):
    """Iterative Douglas-Peucker in projected metres."""
    if len(points) < 3:
        return points
    keep = [False] * len(points)
    keep[0] = keep[-1] = True
    stack = [(0, len(points) - 1)]
    while stack:
        first, last = stack.pop()
        index, distance = 0, 0.0
        for i in range(first + 1, last):
            d = perpendicular(points[i], points[first], points[last])
            if d > distance:
                index, distance = i, d
        if distance > tolerance:
            keep[index] = True
            stack.extend([(first, index), (index, last)])
    return [p for p, k in zip(points, keep) if k]


for slug, relation_id, tolerance in ROUTES:
    data = fetch(relation_id)
    lines = []
    collect(data.get('route'), lines)
    simplified = [
        [to_wgs84(x, y) for x, y in simplify(line, tolerance)]
        for line in lines
    ]
    simplified = [line for line in simplified if len(line) >= 2]
    feature_collection = {
        'type': 'FeatureCollection',
        'features': [{
            'type': 'Feature',
            'properties': {
                'name': data.get('name'),
                'name_cy': data.get('tags', {}).get('name:cy'),
                'osm_relation': relation_id,
                'source': f'https://www.openstreetmap.org/relation/{relation_id}',
                'attribution': '© OpenStreetMap contributors, ODbL 1.0',
            },
            'geometry': {'type': 'MultiLineString', 'coordinates': simplified},
        }],
    }
    path = f'{OUT}/{slug}.geojson'
    with open(path, 'w') as handle:
        json.dump(feature_collection, handle, separators=(',', ':'), ensure_ascii=False)
    points = sum(len(line) for line in simplified)
    print(f'{slug}: {len(lines)} ways, {points} points, {len(json.dumps(feature_collection, separators=(",", ":"))) // 1024} KB')
