"""Generate an Ordnance Survey style contour texture as SVG via marching squares over a smooth height field."""
import math
import random
import sys

random.seed(1331)
W, H = 640, 960
STEP = 12
cols, rows = W // STEP + 1, H // STEP + 1

# Height field: a few hills and a ridge
hills = [(random.uniform(0, W), random.uniform(0, H), random.uniform(120, 260), random.uniform(0.6, 1.2)) for _ in range(7)]


def height(x, y):
    h = 0.0
    for hx, hy, r, a in hills:
        h += a * math.exp(-((x - hx) ** 2 + (y - hy) ** 2) / (2 * r * r))
    h += 0.15 * math.sin(x / 90.0) * math.cos(y / 120.0)
    return h


grid = [[height(c * STEP, r * STEP) for c in range(cols)] for r in range(rows)]
lo = min(min(row) for row in grid)
hi = max(max(row) for row in grid)
levels = [lo + (hi - lo) * i / 22 for i in range(1, 22)]


def interp(p1, p2, v1, v2, level):
    t = (level - v1) / (v2 - v1) if v2 != v1 else 0.5
    return (p1[0] + t * (p2[0] - p1[0]), p1[1] + t * (p2[1] - p1[1]))


paths = {'minor': [], 'index': []}
for index, level in enumerate(levels):
    segments = []
    for r in range(rows - 1):
        for c in range(cols - 1):
            x, y = c * STEP, r * STEP
            corners = [(x, y), (x + STEP, y), (x + STEP, y + STEP), (x, y + STEP)]
            values = [grid[r][c], grid[r][c + 1], grid[r + 1][c + 1], grid[r + 1][c]]
            points = []
            for i in range(4):
                j = (i + 1) % 4
                if (values[i] < level) != (values[j] < level):
                    points.append(interp(corners[i], corners[j], values[i], values[j], level))
            if len(points) == 2:
                segments.append(points)
            elif len(points) == 4:
                segments.append(points[:2])
                segments.append(points[2:])
    d = ''.join(f'M{a[0]:.0f} {a[1]:.0f}L{b[0]:.0f} {b[1]:.0f}' for a, b in segments)
    # Every fifth contour is an index contour, drawn heavier, as on OS maps
    paths['index' if index % 5 == 4 else 'minor'].append(d)

svg = (
    f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" preserveAspectRatio="xMidYMid slice" fill="none" stroke="#c9a15b" stroke-linecap="round">'
    f'<path stroke-opacity=".34" stroke-width="1.1" d="{"".join(paths["minor"])}"/>'
    f'<path stroke-opacity=".55" stroke-width="1.7" d="{"".join(paths["index"])}"/>'
    '</svg>'
)
with open(sys.argv[1], 'w') as handle:
    handle.write(svg)
print(len(svg) // 1024, 'KB')
