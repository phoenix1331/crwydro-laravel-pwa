<section x-data="entryList" x-show="$store.router.is('entries')" x-cloak>
    <h1 class="mb-4 text-2xl font-bold">Entries</h1>

    <div class="no-scrollbar -mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1" role="group" aria-label="Filter by tag">
        <button type="button" class="min-h-10 shrink-0 rounded-full border px-4 text-sm font-semibold"
                :class="filter === null ? 'border-moss-600 bg-moss-600 text-white' : 'border-peat-200 bg-white text-peat-800'"
                @click="filter = null">All</button>
        <template x-for="option in tags" :key="option.key">
            <button type="button" class="min-h-10 shrink-0 rounded-full border px-4 text-sm font-semibold"
                    :class="filter === option.key ? 'border-moss-600 bg-moss-600 text-white' : 'border-peat-200 bg-white text-peat-800'"
                    @click="filter = option.key" x-text="option.en"></button>
        </template>
    </div>

    <ul class="space-y-3" x-show="entries.length">
        <template x-for="entry in entries" :key="entry.id">
            <li>
                <a :href="`#/entries/${entry.id}`"
                   class="flex items-center gap-3 rounded-2xl border border-peat-100 bg-white p-3 shadow-sm active:bg-peat-50">
                    <img x-show="covers[entry.id]" :src="covers[entry.id] ? photoUrl(covers[entry.id]) : ''" alt=""
                         class="size-16 shrink-0 rounded-xl bg-peat-100 object-cover">
                    <div x-show="! covers[entry.id]" class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-peat-100 text-peat-400" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
                            <path d="M3 18 9 9l4 6 2-3 6 6z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold" x-text="entry.title"></p>
                        <p class="mt-0.5 text-sm text-peat-600">
                            <span x-text="tagLabel(entry.tag)"></span> ·
                            <span x-text="formatRelative(entry.recordedAt)"></span>
                        </p>
                    </div>
                    @include('shell.partials.sync-badge', ['status' => 'entry.syncStatus'])
                </a>
            </li>
        </template>
    </ul>

    <div x-show="loaded && ! entries.length && filter" x-cloak
         class="rounded-2xl border border-dashed border-peat-200 bg-white p-6 text-center text-peat-600">
        No <span x-text="filter ? tagLabel(filter).toLowerCase() : ''"></span> entries yet.
    </div>

    <div x-show="loaded && ! entries.length && ! filter" x-cloak
         class="rounded-2xl border border-dashed border-peat-200 bg-white p-8 text-center">
        <p class="text-lg font-semibold">No entries yet</p>
        <p class="mt-1 text-peat-600">Spotted something on the hill? Log it, signal or not.</p>
        <a href="#/entries/new" class="btn-primary mt-5">Add your first entry</a>
    </div>
</section>
