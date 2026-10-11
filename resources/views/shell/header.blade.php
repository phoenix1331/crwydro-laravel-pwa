<header class="sticky top-0 z-20 bg-moss-700 text-white shadow-md" style="padding-top: env(safe-area-inset-top)">
    <div class="flex h-14 items-center justify-between px-4">
        <a href="#/" class="flex items-center gap-2 text-lg font-bold tracking-tight">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                <path d="M2 20 9 7l4 7 3-4 6 10z" />
            </svg>
            {{ config('app.name') }}
        </a>

        <div id="header-status" class="flex items-center gap-2 text-sm"></div>
    </div>

    <div x-data x-show="! $store.network.online" x-cloak
         class="bg-bracken-500 px-4 py-2 text-center text-sm font-semibold text-white" role="status">
        You're offline. Entries are saved on this device.
    </div>
</header>
