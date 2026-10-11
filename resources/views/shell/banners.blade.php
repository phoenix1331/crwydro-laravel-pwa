<div x-data class="sticky top-[calc(3.5rem+env(safe-area-inset-top))] z-10 md:top-0">
    <div x-show="! $store.network.online" x-cloak class="bg-warn-soft px-4 py-2 text-center text-sm font-bold text-warn" role="status" x-text="$t('banner.offline')">
        Dim signal? Dim problem. Entries are saved on this device.
    </div>

    <a x-show="$store.storage.nearlyFull() && ! $store.router.is('sync')" x-cloak href="#/sync"
       class="block bg-danger-soft px-4 py-2 text-center text-sm font-bold text-danger underline-offset-4 hover:underline" role="status"
       x-text="$t('storage.nearly_full_toast')"></a>

    <a x-show="$store.auth.authRequired && ! $store.router.is('login')" x-cloak href="#/login"
       class="block bg-danger-soft px-4 py-2 text-center text-sm font-bold text-danger underline-offset-4 hover:underline" role="status" x-text="$t('banner.auth')">
        Log in again to sync. Your entries are safe on this device.
    </a>
</div>
