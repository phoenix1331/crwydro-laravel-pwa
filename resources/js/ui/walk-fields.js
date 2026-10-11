import { lookOutFor, walkLabel, walkNote, WALKS } from '../data/walks';
import { localised } from './format';

/**
 * Walk selector and "look out for" quick picks shared by the entry forms.
 */
export function walkFields() {
    return {
        walks: WALKS,
        routeId: null,
        hint: null,
        walkLabel,
        walkNote: localised(walkNote),

        quickPicks() {
            return lookOutFor(this.routeId);
        },

        pickSpecies(item) {
            this.title = `${item.cy} (${item.en})`;
            this.tag = item.tag;
            this.hint = item.note ? walkNote(item) : null;
        },
    };
}
