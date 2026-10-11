import Alpine from 'alpinejs';
import { pwaStore } from './pwa';
import { routerStore } from './router';
import { networkStore } from './ui/network';

Alpine.store('router', routerStore());
Alpine.store('network', networkStore());
Alpine.store('pwa', pwaStore());

window.Alpine = Alpine;

Alpine.start();
