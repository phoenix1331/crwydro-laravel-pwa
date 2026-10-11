import Alpine from 'alpinejs';
import { pwaStore } from './pwa';
import { routerStore } from './router';
import { authStore, loginForm } from './ui/auth';
import { entryDetail, entryEdit, entryList } from './ui/entries';
import { entryForm } from './ui/entry-form';
import { networkStore } from './ui/network';
import { syncStore } from './ui/sync';
import { toastStore } from './ui/toast';

Alpine.store('router', routerStore());
Alpine.store('network', networkStore());
Alpine.store('pwa', pwaStore());
Alpine.store('toast', toastStore());
Alpine.store('auth', authStore());
Alpine.store('sync', syncStore());

Alpine.data('entryForm', entryForm);
Alpine.data('entryList', entryList);
Alpine.data('entryDetail', entryDetail);
Alpine.data('entryEdit', entryEdit);
Alpine.data('loginForm', loginForm);

window.Alpine = Alpine;

Alpine.start();
