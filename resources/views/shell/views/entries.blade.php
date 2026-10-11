<section x-data="entryList" x-show="$store.router.is('entries')" x-cloak>
    <h1 class="mb-4 text-2xl font-bold">Entries</h1>

    <div class="no-scrollbar -mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1" role="group" aria-label="Filter by tag">
        <button type="button" class="chip" :class="filter === null && 'chip-on'" :aria-pressed="filter === null"
                @click="filter = null">All</button>
        <template x-for="option in tags" :key="option.key">
            <button type="button" class="chip" :class="filter === option.key && 'chip-on'" :aria-pressed="filter === option.key"
                    @click="filter = option.key" x-text="option.en"></button>
        </template>
    </div>

    <ul class="space-y-3" x-show="entries.length">
        <template x-for="entry in entries" :key="entry.id">
            <li>
                <a :href="`#/entries/${entry.id}`"
                   class="flex items-center gap-3 rounded-2xl border border-line bg-surface p-3 shadow-sm active:bg-sunk">
                    <img x-show="covers[entry.id] && photoUrl(covers[entry.id])" :src="covers[entry.id] ? (photoUrl(covers[entry.id]) ?? '') : ''" alt=""
                         class="size-16 shrink-0 rounded-xl bg-sunk object-cover">
                    <div x-show="! (covers[entry.id] && photoUrl(covers[entry.id]))" class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-sunk text-faint" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
                            <path d="M3 18 9 9l4 6 2-3 6 6z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold" x-text="entry.title"></p>
                        <p class="mt-0.5 flex gap-3 text-sm text-muted">
                            <span x-text="tagLabel(entry.tag)"></span>
                            <time :datetime="entry.recordedAt" x-text="formatRelative(entry.recordedAt)"></time>
                        </p>
                    </div>
                    @include('shell.partials.sync-badge', ['status' => 'entry.syncStatus'])
                </a>
            </li>
        </template>
    </ul>

    <div x-show="loaded && ! entries.length && filter" x-cloak
         class="rounded-2xl border border-dashed border-line bg-surface p-6 text-center text-muted">
        No <span x-text="filter ? tagLabel(filter).toLowerCase() : ''"></span> entries yet.
    </div>

    <div x-show="loaded && ! entries.length && ! filter" x-cloak
         class="rounded-2xl border border-dashed border-line bg-surface p-8 text-center">
        <p class="text-lg font-bold">No entries yet</p>
        <p class="mt-1 text-muted">Spotted something on the hill? Log it, signal or not.</p>
        <a href="#/entries/new" class="btn-primary mt-5">Add your first entry</a>
    </div>
</section>
