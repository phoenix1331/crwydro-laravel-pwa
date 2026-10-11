import { liveQuery } from 'dexie';
import { deleteEntry, getEntry, listEntries, updateEntry } from '../data/entries';
import { TAGS, tagLabel } from '../data/tags';
import { formatCoords, formatDateTime, formatRelative, SYNC_BADGES } from './format';

const helpers = {
    tagLabel,
    formatDateTime,
    formatRelative,
    formatCoords,
    badge: (status) => SYNC_BADGES[status] ?? SYNC_BADGES.local,
};

/**
 * Subscribe to a Dexie live query, replacing any previous subscription.
 */
function subscribe(component, query, apply) {
    component.subscription?.unsubscribe();
    component.subscription = liveQuery(query).subscribe({
        next: apply,
        error: (error) => console.error(error),
    });
}

export function entryList() {
    return {
        ...helpers,
        tags: TAGS,
        filter: null,
        entries: [],
        loaded: false,
        subscription: null,

        init() {
            this.load();
            this.$watch('filter', () => this.load());
        },

        load() {
            subscribe(this, () => listEntries({ tag: this.filter }), (entries) => {
                this.entries = entries;
                this.loaded = true;
            });
        },
    };
}

export function entryDetail() {
    return {
        ...helpers,
        entry: null,
        subscription: null,

        currentId: null,

        // Called from x-effect so it re-runs whenever the route changes
        load(route, id) {
            if (route !== 'entry' || !id || id === this.currentId) {
                return;
            }

            this.currentId = id;
            subscribe(this, () => getEntry(id), (entry) => (this.entry = entry ?? null));
        },

        async remove() {
            if (!confirm('Delete this entry? It will also be removed from the server when you next sync.')) {
                return;
            }

            await deleteEntry(this.entry.id);
            this.$store.toast.show('Entry deleted.');
            this.$store.router.go('/');
        },
    };
}

export function entryEdit() {
    return {
        tags: TAGS,
        id: null,
        title: '',
        notes: '',
        tag: 'flower',
        error: null,
        saving: false,
        missing: false,

        // Called from x-effect so the form reloads each time the edit route opens
        async load(route, id) {
            if (route !== 'edit' || !id) {
                return;
            }

            const entry = await getEntry(id);

            this.missing = !entry;
            this.error = null;

            if (entry) {
                Object.assign(this, { id: entry.id, title: entry.title, notes: entry.notes, tag: entry.tag });
            }
        },

        async save() {
            this.saving = true;

            try {
                await updateEntry(this.id, { title: this.title, notes: this.notes, tag: this.tag });
            } catch (error) {
                this.error = error.message;
                this.saving = false;

                return;
            }

            this.saving = false;
            this.$store.toast.show('Changes saved on this device.', 'success');
            this.$store.router.go(`/entries/${this.id}`);
        },
    };
}
