{{-- On phones the list and an entry are separate screens; from lg the list stays beside the open entry --}}
<section x-data="entryList" x-show="$store.router.is('entries', 'entry')" x-cloak
         :class="$store.router.is('entry') && 'max-lg:hidden'" class="min-w-0">
    @include('shell.install-card')

    <div class="flex items-end justify-between gap-4">
        <h1 class="text-3xl" x-text="$t('entries.title')">Entries</h1>
        <p class="hint hidden pb-1 md:block" x-show="entries.length" x-text="$t('status.count_on_device', { count: entries.length })"></p>
    </div>

    <div class="mt-4 mb-4 flex flex-wrap gap-2" role="group" :aria-label="$t('entries.filter')" aria-label="Filter by tag">
        <button type="button" class="chip" :class="filter === null && 'chip-on'" :aria-pressed="filter === null" @click="filter = null" x-text="$t('entries.all')">All</button>
        <template x-for="option in tags" :key="option.key">
            <button type="button" class="chip" :class="filter === option.key && 'chip-on'" :aria-pressed="filter === option.key"
                    @click="filter = option.key" x-text="option[$store.i18n.locale]"></button>
        </template>
    </div>

    <ul class="card divide-y divide-line overflow-hidden" x-show="entries.length">
        <template x-for="entry in entries" :key="entry.id">
            <li>
                <a :href="`#/entries/${entry.id}`"
                   class="flex items-center gap-3.5 p-3 transition-colors hover:bg-sunk"
                   :class="$store.router.params.id === entry.id && 'bg-accent-soft hover:bg-accent-soft'"
                   :aria-current="$store.router.params.id === entry.id ? 'page' : null">
                    <img x-show="covers[entry.id] && photoUrl(covers[entry.id])" :src="covers[entry.id] ? (photoUrl(covers[entry.id]) ?? '') : ''" alt=""
                         class="size-16 shrink-0 rounded-control bg-sunk object-cover">
                    <div x-show="! (covers[entry.id] && photoUrl(covers[entry.id]))"
                         class="flex size-16 shrink-0 items-center justify-center rounded-control bg-sunk text-faint" aria-hidden="true">
                        @include('shell.icon', ['name' => 'peak', 'class' => 'size-7', 'stroke' => 1.6])
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="line-clamp-2 font-bold leading-snug" x-text="entry.title"></p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
                            <span x-text="tagLabel(entry.tag)"></span>
                            <time :datetime="entry.recordedAt" x-text="formatRelative(entry.recordedAt)"></time>
                            <span class="inline-flex items-center gap-1.5 font-bold" :class="badge(entry.syncStatus).text">
                                <span class="size-2 rounded-full" :class="badge(entry.syncStatus).dot" aria-hidden="true"></span>
                                <span x-text="badge(entry.syncStatus).label"></span>
                            </span>
                        </p>
                    </div>
                </a>
            </li>
        </template>
    </ul>

    <div x-show="loaded && ! entries.length && filter" x-cloak class="card border-dashed p-6 text-center text-muted">
        <span x-text="filter ? $t('entries.none_for_tag', { tag: tagLabel(filter).toLowerCase() }) : ''"></span>
    </div>

    <div x-show="loaded && ! entries.length && ! filter" x-cloak class="card border-dashed p-8 text-center">
        <p class="text-lg font-bold" x-text="$t('entries.empty_title')">No entries yet</p>
        <p class="hint mt-1" x-text="$t('entries.empty_body')">Spotted something on the hill? Log it, signal or not.</p>
        <a href="#/entries/new" class="btn-primary mt-5" x-text="$t('entries.empty_cta')">Add your first entry</a>
    </div>
</section>
