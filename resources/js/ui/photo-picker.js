import { MAX_PHOTOS } from '../data/photos';
import { t } from '../i18n';
import { blobUrl, revokeUrl } from './object-urls';
import { resizePhoto } from './resize';

let counter = 0;

/**
 * Shared state for choosing photos in a form. Resized photos wait in memory
 * until the form is saved, then go into Dexie in the same transaction.
 */
export function photoPicker() {
    return {
        pending: [],
        existingCount: 0,
        resizing: false,
        photoError: null,

        // A method, not a getter: the components spread this object, which would freeze a getter's value
        remaining() {
            return MAX_PHOTOS - this.existingCount - this.pending.length;
        },

        async pick(event) {
            const files = [...(event.target.files ?? [])];
            event.target.value = '';
            this.photoError = null;

            if (files.length > this.remaining()) {
                this.photoError = t('photos.too_many', { max: MAX_PHOTOS });
            }

            this.resizing = true;

            try {
                for (const file of files.slice(0, Math.max(0, this.remaining()))) {
                    const photo = await resizePhoto(file);
                    const key = `pending-${++counter}`;

                    this.pending.push({ ...photo, key, url: blobUrl(key, photo.thumb) });
                }
            } catch {
                this.photoError = t('photos.unreadable');
            } finally {
                this.resizing = false;
            }
        },

        removePending(key) {
            revokeUrl(key);
            this.pending = this.pending.filter((photo) => photo.key !== key);
        },

        takePending() {
            const photos = this.pending.map(({ blob, thumb, width, height }) => ({ blob, thumb, width, height }));

            this.pending.forEach((photo) => revokeUrl(photo.key));
            this.pending = [];

            return photos;
        },
    };
}
