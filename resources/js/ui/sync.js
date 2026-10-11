import Alpine from 'alpinejs';
import { liveQuery } from 'dexie';
import { db, getMeta } from '../data/db';
import { SYNC_FAILED, SYNC_SYNCED } from '../data/entries';
import { t } from '../i18n';
import { backgroundSyncSupported, requestBackgroundSync } from '../sync/background';
import {
    nextRetryAt,
    RESULT_AUTH_REQUIRED,
    RESULT_DONE,
    RESULT_INTERRUPTED,
    RESULT_OFFLINE,
    retryFailed,
    runSync,
} from '../sync/processor';

// Translation keys rather than sentences, so a language switch re-translates the message
const MESSAGES = {
    [RESULT_OFFLINE]: 'sync.offline',
    [RESULT_INTERRUPTED]: 'sync.interrupted',
    [RESULT_AUTH_REQUIRED]: 'sync.auth',
};

/**
 * Sync triggers: app open, `online`, returning to the app, the Sync now
 * button, any local change, and Background Sync where the browser has it.
 */
export function syncStore() {
    return {
        running: false,
        unsynced: 0,
        failed: 0,
        lastSyncAt: null,
        lastMessage: null,
        retryAt: null,
        retryTimer: null,
        backgroundSync: backgroundSyncSupported(),

        init() {
            liveQuery(async () => ({
                unsynced: await db.entries.where('syncStatus').notEqual(SYNC_SYNCED).count(),
                failed: await db.entries.where('syncStatus').equals(SYNC_FAILED).count(),
                lastSyncAt: await getMeta('lastSyncAt'),
            })).subscribe({
                next: (state) => Object.assign(this, state),
                error: (error) => console.error(error),
            });

            window.addEventListener('online', () => this.trigger('online'));
            window.addEventListener('crwydro:sync-requested', () => this.trigger('change'));
            document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && this.trigger('visible'));

            this.trigger('open');
        },

        async trigger(reason = 'manual') {
            if (reason !== 'manual' && reason !== 'open') {
                requestBackgroundSync();
            }

            if (this.running || !(await getMeta('token'))) {
                return;
            }

            this.running = true;
            let result;

            try {
                result = await runSync();
            } catch (error) {
                console.error(error);
                result = { status: 'error' };
            } finally {
                this.running = false;
            }

            this.lastMessage = MESSAGES[result.status] ?? (result.status === 'error' ? 'sync.error' : null);

            if (reason === 'manual') {
                this.announce(result);
            }

            await this.scheduleRetry();
        },

        // Failures already show on the Sync view, which is where Sync now lives
        announce(result) {
            if (result.status === RESULT_DONE) {
                const parts = [t('sync.sent', { count: result.sent })];
                const updated = (result.pull?.applied ?? 0) + (result.pull?.deleted ?? 0);

                if (updated) parts.push(t('sync.updated', { count: updated }));
                if (result.failed) parts.push(t('sync.failed', { count: result.failed }));
                if (result.deferred) parts.push(t('sync.waiting', { count: result.deferred }));

                Alpine.store('toast').show(t('sync.finished', { parts: parts.join(', ') }), result.failed ? 'warning' : 'success');

                return;
            }
        },

        /**
         * While the app is open, wake up when the earliest backed-off row is due.
         */
        async scheduleRetry() {
            clearTimeout(this.retryTimer);
            this.retryAt = await nextRetryAt();

            if (this.retryAt) {
                this.retryTimer = setTimeout(() => this.trigger('retry'), Math.max(1000, this.retryAt - Date.now()));
            }
        },

        async retryFailed() {
            await retryFailed();
            await this.trigger('manual');
        },
    };
}
