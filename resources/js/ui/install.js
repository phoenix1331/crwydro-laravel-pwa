const DISMISS_KEY = 'crwydro.installDismissed';
const REMIND_AFTER = 14 * 24 * 60 * 60 * 1000;

function dismissedRecently() {
    try {
        return Date.now() - Number(localStorage.getItem(DISMISS_KEY) ?? 0) < REMIND_AFTER;
    } catch {
        return false;
    }
}

/**
 * Encourage Add to Home Screen. On iOS this matters most: Safari can clear
 * storage for sites that aren't installed, taking unsynced entries with it.
 */
export function installStore() {
    return {
        deferred: null,
        installed: window.matchMedia?.('(display-mode: standalone)').matches || navigator.standalone === true,
        ios: /iPhone|iPad|iPod/.test(navigator.userAgent),
        dismissed: dismissedRecently(),

        init() {
            window.addEventListener('beforeinstallprompt', (event) => {
                event.preventDefault();
                this.deferred = event;
            });

            window.addEventListener('appinstalled', () => {
                this.installed = true;
                this.deferred = null;
            });
        },

        get offer() {
            return !this.installed && !this.dismissed && (this.deferred !== null || this.ios);
        },

        async install() {
            if (!this.deferred) {
                return;
            }

            this.deferred.prompt();
            const { outcome } = await this.deferred.userChoice;
            this.deferred = null;

            if (outcome !== 'accepted') {
                this.dismiss();
            }
        },

        dismiss() {
            this.dismissed = true;

            try {
                localStorage.setItem(DISMISS_KEY, String(Date.now()));
            } catch {
                // Not remembered across visits; it simply reappears next time
            }
        },
    };
}
