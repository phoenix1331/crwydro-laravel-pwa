<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-peat-200 bg-white/95 backdrop-blur"
     style="padding-bottom: env(safe-area-inset-bottom)" x-data>
    <div class="mx-auto grid h-18 max-w-xl grid-cols-3 items-center">
        <a href="#/" class="flex flex-col items-center gap-1 py-2 text-xs font-semibold"
           :class="$store.router.is('entries', 'entry', 'edit') ? 'text-moss-600' : 'text-peat-400'">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h10" />
            </svg>
            Entries
        </a>

        <a href="#/entries/new" class="-mt-6 flex flex-col items-center gap-1 text-xs font-semibold text-peat-800" aria-label="New entry">
            <span class="flex size-16 items-center justify-center rounded-full bg-bracken-500 text-white shadow-lg ring-4 ring-white active:scale-95">
                <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </span>
            New
        </a>

        <a href="#/sync" class="flex flex-col items-center gap-1 py-2 text-xs font-semibold"
           :class="$store.router.is('sync', 'login') ? 'text-moss-600' : 'text-peat-400'">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 12a8 8 0 0 1-14.3 4.9M4 12a8 8 0 0 1 14.3-4.9M18 3v4h-4M6 21v-4h4" />
            </svg>
            Sync
        </a>
    </div>
</nav>
