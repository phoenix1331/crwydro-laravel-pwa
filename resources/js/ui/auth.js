import { liveQuery } from 'dexie';
import { authState, login } from '../data/auth';
import { t } from '../i18n';

export function authStore() {
    return {
        loggedIn: false,
        user: null,
        authRequired: false,

        init() {
            liveQuery(authState).subscribe({
                next: (state) => Object.assign(this, state),
                error: (error) => console.error(error),
            });
        },
    };
}

export function loginForm() {
    return {
        email: '',
        password: '',
        error: null,
        busy: false,

        async submit() {
            this.busy = true;
            this.error = null;

            try {
                const user = await login(this.email.trim(), this.password);

                this.password = '';
                this.$store.toast.show(t('login.logged_in', { name: user.name }), 'success');
                window.dispatchEvent(new CustomEvent('crwydro:sync-requested'));
                this.$store.router.go('/sync');
            } catch (error) {
                this.error = error.message;
            } finally {
                this.busy = false;
            }
        },
    };
}
