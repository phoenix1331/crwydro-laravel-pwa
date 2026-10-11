<section x-data="walkList" x-show="$store.router.is('walks')" x-cloak class="mx-auto max-w-6xl">
    <h1 class="text-3xl">Walks</h1>
    <p class="hint mt-1 max-w-[60ch]">Featured routes are saved on this device, so you can attach entries to them with no signal.</p>

    <template x-for="area in areas" :key="area.key">
        <section class="mt-10" :aria-labelledby="`area-${area.key}`">
            <h2 :id="`area-${area.key}`" class="text-xl">
                <span x-text="area.cy"></span>
                <span class="font-normal text-muted" x-text="area.en"></span>
            </h2>

            <ul class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="walk in area.walks" :key="walk.id">
                    <li class="card flex flex-col overflow-hidden">
                        <svg viewBox="0 0 160 96" class="h-28 w-full bg-sunk text-accent" fill="none" aria-hidden="true">
                            <path :d="sketches[walk.id] ?? ''" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex flex-1 flex-col p-4">
                            <p class="text-lg font-extrabold leading-snug" x-text="walk.cy"></p>
                            <p class="hint" x-text="walk.en"></p>
                            <p class="mt-2 flex-1 text-sm" x-text="walk.note"></p>
                            <a :href="`#/map/${walk.id}`" class="btn-quiet mt-3 self-start">Show on map</a>
                        </div>
                    </li>
                </template>
            </ul>

            <div class="mt-4">
                <p class="label">Look out for</p>
                <ul class="flex flex-wrap gap-2 text-sm">
                    <template x-for="item in area.lookOut" :key="item.cy">
                        <li class="rounded-full border border-line bg-surface px-3 py-1.5">
                            <span class="font-bold" x-text="item.cy"></span>
                            <span class="text-muted" x-text="`(${item.en})`"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </section>
    </template>

    <p class="hint mt-10 text-xs">Route lines &copy; <a href="https://www.openstreetmap.org/copyright" class="underline">OpenStreetMap contributors</a>, ODbL.</p>
</section>
