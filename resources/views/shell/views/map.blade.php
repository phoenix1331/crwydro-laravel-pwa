<section x-data="deviceMap" x-effect="show($store.router.name, $store.router.params.id)" x-show="$store.router.is('map')" x-cloak
         class="-mx-4 -mt-5 -mb-28 md:-mx-8 md:-mt-8 md:-mb-10 lg:-mx-10">
    <div class="relative h-[calc(100dvh-8rem-env(safe-area-inset-top)-env(safe-area-inset-bottom))] md:h-dvh">
        <div x-ref="map" class="absolute inset-0 z-0 bg-sunk"></div>

        {{-- Laptop: walk picker and tag key float over the map --}}
        <div class="absolute top-4 left-4 z-[500] hidden w-72 rounded-card border border-line bg-surface/95 p-4 shadow-lg backdrop-blur md:block">
            <label for="map-walk" class="label" x-text="$t('map.walk')">Walk</label>
            <select id="map-walk" class="field min-h-10 text-sm" @change="$store.router.go($event.target.value ? `/map/${$event.target.value}` : '/map')">
                <option value="" x-text="$t('map.all_wales')">All of Wales</option>
                <template x-for="walk in walks" :key="walk.id">
                    <option :value="walk.id" x-text="walk.cy" :selected="focusedWalk?.id === walk.id"></option>
                </template>
            </select>

            <p class="label mt-4" x-text="$t('map.by_tag')">Entries by tag</p>
            <ul class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-sm">
                <template x-for="tag in tags" :key="tag.key">
                    <li class="flex items-center gap-2">
                        <span class="size-3 rounded-full ring-2 ring-surface" :style="`background: ${tagColour(tag.key)}`" aria-hidden="true"></span>
                        <span x-text="tag[$store.i18n.locale]"></span>
                    </li>
                </template>
            </ul>
        </div>

        <div x-show="focusedWalk" x-cloak class="absolute top-3 left-3 right-16 z-[500] rounded-control bg-surface/95 px-3 py-2 text-sm shadow md:hidden">
            <span class="font-bold" x-text="focusedWalk?.cy"></span>
            <span class="text-muted" x-text="focusedWalk ? `(${focusedWalk.en})` : ''"></span>
        </div>

        <button type="button" @click="locate" :aria-label="$t('map.locate')" aria-label="Show my location"
                class="absolute right-3 bottom-6 z-[500] flex size-12 items-center justify-center rounded-full bg-surface text-accent shadow-lg md:right-4 md:bottom-8">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4" /><path d="M12 2v3M12 19v3M2 12h3M19 12h3" />
            </svg>
        </button>

        <p x-show="! $store.network.online" x-cloak
           class="absolute bottom-6 left-3 right-20 z-[500] rounded-control bg-surface/95 px-3 py-2 text-xs text-ink shadow md:right-auto md:left-4 md:max-w-72" x-text="$t('map.offline')">
            Offline: the base map only shows areas you viewed with signal. Routes and your entries are always here.
        </p>
    </div>
</section>
