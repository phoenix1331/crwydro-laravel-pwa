<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-surface/95 backdrop-blur"
     style="padding-bottom: env(safe-area-inset-bottom)" x-data>
    <div class="mx-auto grid h-18 max-w-xl grid-cols-5 items-center">
        <a href="#/" class="flex flex-col items-center gap-1 py-2 text-xs font-bold"
           :class="$store.router.is('entries', 'entry', 'edit') ? 'text-accent' : 'text-faint'">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h10" />
            </svg>
            Entries
        </a>

        <a href="#/walks" class="flex flex-col items-center gap-1 py-2 text-xs font-bold"
           :class="$store.router.is('walks') ? 'text-accent' : 'text-faint'">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 19c4-1 3-6 7-7s4-6 9-7" /><circle cx="4" cy="19" r="1.5" /><circle cx="20" cy="5" r="1.5" />
            </svg>
            Walks
        </a>

        <a href="#/entries/new" class="-mt-6 flex flex-col items-center gap-1 text-xs font-bold text-ink" aria-label="New entry">
            <span class="flex size-16 items-center justify-center rounded-full bg-accent text-accent-ink shadow-lg ring-4 ring-canvas active:scale-95">
                <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </span>
            New
        </a>

        <a href="#/map" class="flex flex-col items-center gap-1 py-2 text-xs font-bold"
           :class="$store.router.is('map') ? 'text-accent' : 'text-faint'">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2zM9 4v14M15 6v14" />
            </svg>
            Map
        </a>

        <a href="#/sync" class="flex flex-col items-center gap-1 py-2 text-xs font-bold"
           :class="$store.router.is('sync', 'login') ? 'text-accent' : 'text-faint'">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 12a8 8 0 0 1-14.3 4.9M4 12a8 8 0 0 1 14.3-4.9M18 3v4h-4M6 21v-4h4" />
            </svg>
            Sync
        </a>
    </div>
</nav>
