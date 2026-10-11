<section x-data="deviceMap" x-effect="show($store.router.name, $store.router.params.id)" x-show="$store.router.is('map')" x-cloak
         class="-mx-4 -mt-5 -mb-28 md:-mx-8 md:-mt-8 md:-mb-10 lg:-mx-10">
    <div class="relative h-[calc(100dvh-8rem-env(safe-area-inset-top)-env(safe-area-inset-bottom))] md:h-dvh">
        <div x-ref="map" class="absolute inset-0 z-0 bg-sunk"></div>

        <div x-show="focusedWalk" x-cloak class="absolute top-3 left-3 right-16 z-[500] rounded-xl bg-surface/95 px-3 py-2 text-sm shadow">
            <span class="font-bold" x-text="focusedWalk?.cy"></span>
            <span class="text-muted" x-text="focusedWalk ? `(${focusedWalk.en})` : ''"></span>
        </div>

        <button type="button" @click="locate" aria-label="Show my location"
                class="absolute right-3 bottom-6 z-[500] flex size-12 items-center justify-center rounded-full bg-surface text-accent shadow-lg">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4" /><path d="M12 2v3M12 19v3M2 12h3M19 12h3" />
            </svg>
        </button>

        <p x-show="! $store.network.online" x-cloak
           class="absolute bottom-6 left-3 right-20 z-[500] rounded-xl bg-surface/95 px-3 py-2 text-xs text-ink shadow">
            Offline: the base map only shows areas you viewed with signal. Routes and your entries are always here.
        </p>
    </div>
</section>
