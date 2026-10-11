import { getMeta, setMeta } from '../data/db';
import { createEntry } from '../data/entries';
import { TAGS } from '../data/tags';
import { nowIso } from '../data/time';
import { walkFields } from './walk-fields';
import { getPosition } from './geo';
import { photoPicker } from './photo-picker';

const LOCATION_MESSAGES = {
    denied: 'Saved without location: location access is turned off for Crwydro.',
    unavailable: 'Saved without location: no GPS fix yet.',
    unsupported: 'Saved without location: this device has no GPS.',
    skipped: 'Saved without location.',
};

export function entryForm() {
    return {
        ...photoPicker(),
        ...walkFields(),
        tags: TAGS,
        title: '',
        notes: '',
        tag: 'flower',
        state: 'idle', // idle | locating | saving
        error: null,
        skipLocation: null,

        async init() {
            this.routeId = await getMeta('lastRouteId');
        },

        reset() {
            this.title = '';
            this.notes = '';
            this.tag = 'flower';
            this.state = 'idle';
            this.hint = null;
            this.takePending();
            this.error = null;
        },

        async save() {
            if (!this.title.trim()) {
                this.error = 'Give it a title, even a short one.';

                return;
            }

            this.error = null;
            const recordedAt = nowIso();

            this.state = 'locating';
            const { location, reason } = await Promise.race([
                getPosition(),
                new Promise((resolve) => (this.skipLocation = () => resolve({ location: null, reason: 'skipped' }))),
            ]);

            this.state = 'saving';

            try {
                await createEntry({
                    title: this.title,
                    notes: this.notes,
                    tag: this.tag,
                    routeId: this.routeId || null,
                    location,
                    recordedAt,
                    photos: this.pending.map(({ blob, thumb, width, height }) => ({ blob, thumb, width, height })),
                });
            } catch (error) {
                this.state = 'idle';
                this.error = error.message;

                return;
            }

            await setMeta('lastRouteId', this.routeId || null);
            window.dispatchEvent(new CustomEvent('crwydro:sync-requested'));
            this.$store.toast.show(location ? 'Saved on this device.' : LOCATION_MESSAGES[reason], location ? 'success' : 'warning');
            this.reset();
            this.$store.router.go('/');
        },
    };
}
