import { liveQuery } from 'dexie';
import { deleteEntry, getEntry, listEntries, updateEntry } from '../data/entries';
import { addPhotos, coverPhotos, photosFor, removeUnsyncedPhoto } from '../data/photos';
import { fetchFullPhoto } from '../sync/pull';
import { TAGS, tagLabel } from '../data/tags';
import { t } from '../i18n';
import { formatCoords, formatDateTime, formatRelative, localised, syncBadge } from './format';
import { photoUrl } from './object-urls';
import { photoPicker } from './photo-picker';
import { walkFields } from './walk-fields';
import { walkLabel } from '../data/walks';
import { osGridRef } from '../data/osgrid';

const helpers = {
    tagLabel: localised(tagLabel),
    formatDateTime: localised(formatDateTime),
    formatRelative: localised(formatRelative),
    formatCoords,
    badge: localised(syncBadge),
    photoUrl,
    walkLabel,
    osGridRef,
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
        opener: null,
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

        /**
         * Photos pulled from the server arrive as thumbnails; the full image
         * downloads the first time it is opened, if there is signal.
         */
        async open(photo) {
            this.enlarged = photo;

            if (!photo.blob) {
                const blob = await fetchFullPhoto(photo.id);

                if (blob && this.enlarged?.id === photo.id) {
                    this.enlarged = { ...photo, blob };
                }
            }
        },

        // Keep Tab inside the photo viewer while it is open
        trapFocus(event, dialog) {
            const buttons = [...dialog.querySelectorAll('button')].filter((button) => button.getClientRects().length);
            const index = buttons.indexOf(document.activeElement);
            const next = (index + (event.shiftKey ? -1 : 1) + buttons.length) % buttons.length;

            event.preventDefault();
            buttons[next]?.focus();
        },

        step(direction) {
            if (!this.enlarged || this.photos.length < 2) {
                return;
            }

            const index = this.photos.findIndex((photo) => photo.id === this.enlarged.id);

            this.open(this.photos[(index + direction + this.photos.length) % this.photos.length]);
        },

        async remove() {
            if (!confirm(t('entry.delete_confirm'))) {
                return;
            }

            await deleteEntry(this.entry.id);
            window.dispatchEvent(new CustomEvent('crwydro:sync-requested'));
            this.$store.toast.show(t('entry.deleted'));
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
            this.$store.toast.show(t('form.changes_saved'), 'success');
            this.$store.router.go(`/entries/${this.id}`);
        },

        async removePhoto(photo) {
            if (confirm(t('photos.remove_confirm'))) {
                await removeUnsyncedPhoto(photo.id);
            }
        },
    };
}
