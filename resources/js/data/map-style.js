/**
 * Shared map styling for the device map and the server-rendered web map.
 * Entries are dots coloured by tag; walk routes are a dashed heather line
 * (like paths on an OS map), so no route can be mistaken for a tag.
 */
export const TAG_COLOURS = {
    flower: '#c8741f',
    bird: '#2f6db5',
    fungus: '#7a4f2a',
    insect: '#127f78',
    view: '#3e6330',
    other: '#5d6b64',
};

export const ROUTE_STYLE = { color: '#8a2d6b', weight: 4, opacity: 0.85, dashArray: '10 7', lineCap: 'round' };

export function tagColour(key) {
    return TAG_COLOURS[key] ?? TAG_COLOURS.other;
}
