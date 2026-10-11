import { lookOutFor, walkLabel, WALKS } from '../data/walks';

/**
 * Walk selector and "look out for" quick picks shared by the entry forms.
 */
export function walkFields() {
    return {
        walks: WALKS,
        routeId: null,
        hint: null,
        walkLabel,

        quickPicks() {
            return lookOutFor(this.routeId);
        },

        pickSpecies(item) {
            this.title = `${item.cy} (${item.en})`;
            this.tag = item.tag;
            this.hint = item.note ?? null;
        },
    };
}
