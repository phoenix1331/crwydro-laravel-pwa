import Alpine from 'alpinejs';
import { pwaStore } from './pwa';
import { routerStore } from './router';
import { entryForm } from './ui/entry-form';
import { networkStore } from './ui/network';
import { toastStore } from './ui/toast';

Alpine.store('router', routerStore());
Alpine.store('network', networkStore());
Alpine.store('pwa', pwaStore());
Alpine.store('toast', toastStore());

Alpine.data('entryForm', entryForm);

window.Alpine = Alpine;

Alpine.start();
