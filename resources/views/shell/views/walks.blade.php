<section x-data="walkList" x-show="$store.router.is('walks')" x-cloak>
    <h1 class="text-2xl font-bold">Walks</h1>
    <p class="mt-1 mb-5 text-peat-600">Featured routes are saved on this device, so you can attach entries to them with no signal.</p>

    <template x-for="area in areas" :key="area.key">
        <div class="mb-6">
            <h2 class="text-lg font-bold">
                <span x-text="area.cy"></span>
                <span class="font-normal text-peat-600" x-text="`(${area.en})`"></span>
            </h2>

            <ul class="mt-2 space-y-2">
                <template x-for="walk in area.walks" :key="walk.id">
                    <li class="rounded-2xl bg-white p-4 shadow-sm">
                        <p class="font-semibold" x-text="walk.cy"></p>
                        <p class="text-sm text-peat-600" x-text="walk.en"></p>
                        <p class="mt-1 text-sm" x-text="walk.note"></p>
                        <a :href="`#/map/${walk.id}`" class="mt-2 inline-flex min-h-10 items-center text-sm font-semibold text-moss-600">Show on map &rarr;</a>
                    </li>
                </template>
            </ul>

            <p class="mt-3 text-sm font-semibold text-peat-800">Look out for</p>
            <ul class="mt-1 flex flex-wrap gap-2 text-sm">
                <template x-for="item in area.lookOut" :key="item.cy">
                    <li class="rounded-full bg-peat-100 px-3 py-1">
                        <span class="font-semibold" x-text="item.cy"></span>
                        <span class="text-peat-600" x-text="`(${item.en})`"></span>
                    </li>
                </template>
            </ul>
        </div>
    </template>

    <p class="text-xs text-peat-600">Route lines &copy; <a href="https://www.openstreetmap.org/copyright" class="underline">OpenStreetMap contributors</a>, ODbL.</p>
</section>
