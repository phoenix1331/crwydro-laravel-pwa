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

            <p x-show="entry.syncError" x-text="entry.syncError"
               class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"></p>

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
