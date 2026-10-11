import Alpine from 'alpinejs';
import { formatBytes, freeSyncedPhotos, requestPersistence, storageReport, WARN_AT } from '../data/storage';
import { localeTag, t } from '../i18n';

export function storageStore() {
    return {
        report: null,
        warned: false,

        init() {
            this.refresh();
            window.addEventListener('crwydro:sync-requested', () => this.refresh());
            document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && this.refresh());
        },

        async refresh() {
            try {
                this.report = await storageReport();
            } catch {
                return;
            }

            // Ask for persistence as soon as there is something worth keeping
            if (this.report.entries && !this.report.persisted && this.report.supported) {
                this.report.persisted = await requestPersistence();
            }

            if (this.nearlyFull() && !this.warned) {
                this.warned = true;
                Alpine.store('toast').show(t('storage.nearly_full_toast'), 'warning', 6000);
            }
        },

        nearlyFull() {
            return (this.report?.ratio ?? 0) >= WARN_AT;
        },

        bytes(value) {
            Alpine.store('i18n').locale;

            return formatBytes(value, localeTag());
        },

        percent() {
            return this.report?.ratio === null || this.report?.ratio === undefined ? null : Math.max(1, Math.round(this.report.ratio * 100));
        },

        async persist() {
            const granted = await requestPersistence();

            await this.refresh();
            Alpine.store('toast').show(granted ? t('storage.persist_granted') : t('storage.persist_refused'), granted ? 'success' : 'warning', 6000);
        },

        async free() {
            const freed = await freeSyncedPhotos();

            await this.refresh();
            Alpine.store('toast').show(t('storage.freed', { size: formatBytes(freed, localeTag()) }), 'success');
        },
    };
}
