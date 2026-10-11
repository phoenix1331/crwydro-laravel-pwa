<header class="sticky top-0 z-20 bg-moss-700 text-white shadow-md" style="padding-top: env(safe-area-inset-top)">
    <div class="flex h-14 items-center justify-between px-4">
        <a href="#/" class="flex items-center gap-2 text-lg font-bold tracking-tight">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                <path d="M2 20 9 7l4 7 3-4 6 10z" />
            </svg>
            {{ config('app.name') }}
        </a>

        <a href="#/sync" x-data class="flex min-h-10 items-center gap-2 rounded-full px-3 text-sm font-semibold"
           :class="$store.sync.unsynced ? 'bg-bracken-500 text-white' : 'bg-white/10 text-white'"
           :aria-label="$store.sync.unsynced ? `${$store.sync.unsynced} entries not yet synced` : 'All entries synced'">
            <svg x-show="$store.sync.running" x-cloak viewBox="0 0 24 24" class="size-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path d="M21 12a9 9 0 1 1-6.2-8.6" stroke-linecap="round" />
            </svg>
            <span x-show="$store.sync.unsynced" x-text="`${$store.sync.unsynced} on device`"></span>
            <span x-show="! $store.sync.unsynced" x-cloak>All synced</span>
        </a>
    </div>

    <div x-data x-show="! $store.network.online" x-cloak
         class="bg-bracken-500 px-4 py-2 text-center text-sm font-semibold text-white" role="status">
        You're offline. Entries are saved on this device.
    </div>

    <a x-data x-show="$store.auth.authRequired && ! $store.router.is('login')" x-cloak href="#/login"
       class="block bg-peat-900 px-4 py-2 text-center text-sm font-semibold text-white" role="status">
        Log in again to sync. Your entries are safe on this device. &rarr;
    </a>
</header>
