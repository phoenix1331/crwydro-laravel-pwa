import { liveQuery } from 'dexie';
import { deleteEntry, getEntry, listEntries, updateEntry } from '../data/entries';
import { addPhotos, coverPhotos, photosFor, removeUnsyncedPhoto } from '../data/photos';
import { TAGS, tagLabel } from '../data/tags';
import { formatCoords, formatDateTime, formatRelative, SYNC_BADGES } from './format';
import { photoUrl } from './object-urls';
import { photoPicker } from './photo-picker';
import { walkFields } from './walk-fields';
import { walkLabel } from '../data/walks';

const helpers = {
    tagLabel,
    formatDateTime,
    formatRelative,
    formatCoords,
    badge: (status) => SYNC_BADGES[status] ?? SYNC_BADGES.local,
    photoUrl,
    walkLabel,
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
        covers: {},
        loaded: false,
        subscription: null,

        init() {
            this.load();
            this.$watch('filter', () => this.load());
        },

        load() {
            subscribe(
                this,
                async () => {
                    const entries = await listEntries({ tag: this.filter });

                    return { entries, covers: await coverPhotos(entries.map((entry) => entry.id)) };
                },
                ({ entries, covers }) => {
                    this.entries = entries;
                    this.covers = covers;
                    this.loaded = true;
                },
            );
        },
    };
}

export function entryDetail() {
    return {
        ...helpers,
        entry: null,
        photos: [],
        enlarged: null,
        subscription: null,
        currentId: null,

        // Called from x-effect so it re-runs whenever the route changes
        load(route, id) {
            if (route !== 'entry' || !id || id === this.currentId) {
                return;
            }

            this.currentId = id;
            subscribe(
                this,
                async () => ({ entry: await getEntry(id), photos: await photosFor(id) }),
                ({ entry, photos }) => {
                    this.entry = entry ?? null;
                    this.photos = photos;
                },
            );
        },

        async remove() {
            if (!confirm('Delete this entry? It will also be removed from the server when you next sync.')) {
                return;
            }

            await deleteEntry(this.entry.id);
            window.dispatchEvent(new CustomEvent('crwydro:sync-requested'));
            this.$store.toast.show('Entry deleted.');
            this.$store.router.go('/');
        },
    };
}

export function entryEdit() {
    return {
        ...photoPicker(),
        ...helpers,
        ...walkFields(),
        tags: TAGS,
        photos: [],
        subscription: null,
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

            this.takePending();
            this.photoError = null;

            if (entry) {
                Object.assign(this, { id: entry.id, title: entry.title, notes: entry.notes, tag: entry.tag, routeId: entry.routeId ?? null, hint: null });
                subscribe(this, () => photosFor(id), (photos) => {
                    this.photos = photos;
                    this.existingCount = photos.length;
                });
            }
        },

        async save() {
            this.saving = true;

            try {
                await updateEntry(this.id, { title: this.title, notes: this.notes, tag: this.tag, routeId: this.routeId || null });

                if (this.pending.length) {
                    await addPhotos(this.id, this.pending.map(({ blob, thumb, width, height }) => ({ blob, thumb, width, height })));
                    this.takePending();
                }
            } catch (error) {
                this.error = error.message;
                this.saving = false;

                return;
            }

            this.saving = false;
            window.dispatchEvent(new CustomEvent('crwydro:sync-requested'));
            this.$store.toast.show('Changes saved on this device.', 'success');
            this.$store.router.go(`/entries/${this.id}`);
        },

        async removePhoto(photo) {
            if (confirm('Remove this photo? It has not been uploaded yet.')) {
                await removeUnsyncedPhoto(photo.id);
            }
        },
    };
}
