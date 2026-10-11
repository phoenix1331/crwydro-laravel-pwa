import { AREAS, LOOK_OUT, WALKS } from '../data/walks';

export function walkList() {
    return {
        areas: Object.entries(AREAS).map(([key, area]) => ({
            key,
            ...area,
            walks: WALKS.filter((walk) => walk.area === key),
            lookOut: LOOK_OUT[key],
        })),
    };
}
