import Alpine from 'alpinejs';
import { routerStore } from './router';
import { networkStore } from './ui/network';

Alpine.store('router', routerStore());
Alpine.store('network', networkStore());

window.Alpine = Alpine;

Alpine.start();
