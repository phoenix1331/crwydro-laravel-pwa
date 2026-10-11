<section x-data="entryDetail" x-effect="load($store.router.name, $store.router.params.id)" x-show="$store.router.is('entry')" x-cloak>
    <template x-if="entry">
        <article>
            <a href="#/" class="mb-3 inline-flex min-h-10 items-center text-sm font-semibold text-moss-600">&larr; Entries</a>

            <div class="flex items-start justify-between gap-3">
                <h1 class="text-2xl font-bold" x-text="entry.title"></h1>
                @include('shell.partials.sync-badge', ['status' => 'entry.syncStatus'])
            </div>

            <p class="mt-1 text-peat-600">
                <span x-text="tagLabel(entry.tag)"></span> ·
                <time :datetime="entry.recordedAt" x-text="formatDateTime(entry.recordedAt)"></time>
            </p>

            <p x-show="entry.routeId" class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-moss-500/15 px-3 py-1 text-sm font-semibold text-moss-700">
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 19c4-1 3-6 7-7s4-6 9-7" /></svg>
                <span x-text="walkLabel(entry.routeId)"></span>
            </p>

            <p x-show="entry.syncError" x-text="entry.syncError"
               class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"></p>

            <div x-show="photos.length" class="mt-5 grid grid-cols-2 gap-2">
                <template x-for="photo in photos" :key="photo.id">
                    <button type="button" class="relative aspect-square overflow-hidden rounded-xl bg-peat-100" @click="enlarged = photo">
                        <img :src="photoUrl(photo)" alt="" class="size-full object-cover">
                        <span x-show="photo.syncStatus !== 'synced'"
                              class="absolute bottom-1 left-1 rounded-full bg-bracken-500 px-2 py-0.5 text-[11px] font-semibold text-white">On device</span>
                    </button>
                </template>
            </div>

            <div x-show="enlarged" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/90 p-4" @click="enlarged = null"
                 @keydown.escape.window="enlarged = null" role="dialog" aria-label="Photo">
                <img :src="enlarged ? photoUrl(enlarged, 'blob') : ''" alt="" class="max-h-full max-w-full rounded-lg">
            </div>

            <p x-show="entry.notes" x-text="entry.notes" class="mt-5 whitespace-pre-line text-lg leading-relaxed"></p>

            <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white p-3">
                    <dt class="text-peat-600">Location</dt>
                    <dd class="mt-0.5 font-semibold" x-text="formatCoords(entry) ?? 'Not recorded'"></dd>
                </div>
                <div class="rounded-xl bg-white p-3">
                    <dt class="text-peat-600">Accuracy</dt>
                    <dd class="mt-0.5 font-semibold" x-text="entry.accuracy !== null ? `± ${Math.round(entry.accuracy)} m` : '–'"></dd>
                </div>
                <div class="rounded-xl bg-white p-3">
                    <dt class="text-peat-600">Altitude</dt>
                    <dd class="mt-0.5 font-semibold" x-text="entry.altitude !== null ? `${Math.round(entry.altitude)} m` : '–'"></dd>
                </div>
                <div class="rounded-xl bg-white p-3">
                    <dt class="text-peat-600">Last edited</dt>
                    <dd class="mt-0.5 font-semibold" x-text="formatRelative(entry.updatedAt)"></dd>
                </div>
            </dl>

            <div class="mt-8 grid grid-cols-2 gap-3">
                <a :href="`#/entries/${entry.id}/edit`" class="btn-primary">Edit</a>
                <button type="button" class="btn-secondary text-red-700" @click="remove">Delete</button>
            </div>
        </article>
    </template>

    <template x-if="! entry">
        <div class="rounded-2xl border border-dashed border-peat-200 bg-white p-8 text-center">
            <p class="text-lg font-semibold">Entry not found</p>
            <a href="#/" class="btn-secondary mt-5">Back to entries</a>
        </div>
    </template>
</section>
