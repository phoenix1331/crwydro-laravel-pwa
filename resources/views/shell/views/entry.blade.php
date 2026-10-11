<section x-data="entryDetail" x-effect="load($store.router.name, $store.router.params.id)" x-show="$store.router.is('entry')" x-cloak
         class="min-w-0 lg:sticky lg:top-8 lg:max-h-[calc(100dvh-4rem)] lg:overflow-y-auto">
    <template x-if="entry">
        <article class="lg:card lg:p-8">
            <a href="#/" class="mb-3 inline-flex min-h-10 items-center gap-1 text-sm font-bold text-accent lg:hidden">
                @include('shell.icon', ['name' => 'back', 'class' => 'size-4', 'stroke' => 2.5])
                Entries
            </a>

            <div class="flex items-start justify-between gap-4">
                <h1 class="text-3xl leading-tight" x-text="entry.title"></h1>
                <span class="mt-1.5 inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-bold"
                      :class="badge(entry.syncStatus).classes" x-text="badge(entry.syncStatus).label"></span>
            </div>

            <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-muted">
                <span class="font-bold text-ink" x-text="tagLabel(entry.tag)"></span>
                <time :datetime="entry.recordedAt" x-text="formatDateTime(entry.recordedAt)"></time>
            </p>

            <a x-show="entry.routeId" :href="`#/map/${entry.routeId}`"
               class="mt-3 inline-flex min-h-9 items-center gap-1.5 rounded-full bg-accent-soft px-3 text-sm font-bold text-accent hover:underline">
                @include('shell.icon', ['name' => 'walks', 'class' => 'size-4'])
                <span x-text="walkLabel(entry.routeId)"></span>
            </a>

            <p x-show="entry.syncError" class="notice mt-4 bg-danger-soft text-danger" role="alert">
                <span x-text="entry.syncError"></span>
                Edit the entry to try again, or use Retry on the Sync page.
            </p>

            <div x-show="photos.length" class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-3">
                <template x-for="photo in photos" :key="photo.id">
                    <button type="button" class="relative aspect-square overflow-hidden rounded-control bg-sunk" @click="open(photo)" aria-label="Open photo">
                        <img x-show="photoUrl(photo)" :src="photoUrl(photo) ?? ''" alt="" class="size-full object-cover transition-transform hover:scale-[1.03]">
                        <span x-show="! photoUrl(photo)" class="flex size-full items-center justify-center p-2 text-center text-xs font-bold text-muted">Downloads with signal</span>
                        <span x-show="photo.syncStatus !== 'synced'"
                              class="absolute bottom-1.5 left-1.5 rounded-full bg-warn-soft px-2 py-0.5 text-xs font-bold text-warn">On this device</span>
                    </button>
                </template>
            </div>

            <p x-show="entry.notes" x-text="entry.notes" class="mt-6 max-w-[65ch] text-lg leading-relaxed whitespace-pre-line"></p>

            <dl class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-card border border-line bg-line text-sm">
                <div class="col-span-2 bg-surface p-4">
                    <dt class="text-muted">Grid reference</dt>
                    <dd class="mt-0.5 text-xl font-extrabold tracking-wide" x-text="osGridRef(entry.lat, entry.lng) ?? 'No location recorded'"></dd>
                    <dd class="hint mt-0.5" x-show="formatCoords(entry)" x-text="formatCoords(entry)"></dd>
                </div>
                <div class="bg-surface p-4">
                    <dt class="text-muted">Accuracy</dt>
                    <dd class="mt-0.5 font-bold" x-text="entry.accuracy !== null ? `Within ${Math.round(entry.accuracy)} m` : 'Not recorded'"></dd>
                </div>
                <div class="bg-surface p-4">
                    <dt class="text-muted">Altitude</dt>
                    <dd class="mt-0.5 font-bold" x-text="entry.altitude !== null ? `${Math.round(entry.altitude)} m` : 'Not recorded'"></dd>
                </div>
            </dl>

            <p class="hint mt-3">Last edited <span x-text="formatRelative(entry.updatedAt)"></span></p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a :href="`#/entries/${entry.id}/edit`" class="btn-primary min-w-32 flex-1 sm:flex-none">Edit</a>
                <button type="button" class="btn-danger min-w-32 flex-1 sm:flex-none" @click="remove">Delete</button>
            </div>
        </article>
    </template>

    <template x-if="! entry">
        <div class="card border-dashed p-8 text-center">
            <p class="text-lg font-bold">This entry isn't on this device</p>
            <a href="#/" class="btn-secondary mt-5">Back to entries</a>
        </div>
    </template>

    <div x-show="enlarged" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
         @click.self="enlarged = null" @keydown.escape.window="enlarged = null"
         @keydown.arrow-right.window="step(1)" @keydown.arrow-left.window="step(-1)"
         role="dialog" aria-modal="true" aria-label="Photo">
        <img :src="enlarged ? (photoUrl(enlarged, 'blob') ?? '') : ''" alt="" class="max-h-full max-w-full rounded-control">

        <button type="button" class="absolute top-4 right-4 flex size-12 items-center justify-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25"
                @click="enlarged = null" aria-label="Close photo">&times;</button>
        <button type="button" x-show="photos.length > 1" class="absolute left-4 flex size-12 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25"
                @click="step(-1)" aria-label="Previous photo">@include('shell.icon', ['name' => 'back'])</button>
        <button type="button" x-show="photos.length > 1" class="absolute right-4 flex size-12 rotate-180 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25"
                @click="step(1)" aria-label="Next photo">@include('shell.icon', ['name' => 'back'])</button>
    </div>
</section>

{{-- Laptop: what sits beside the list before an entry is chosen --}}
<div x-data x-show="$store.router.is('entries')" x-cloak class="hidden lg:block">
    <div class="sticky top-8 flex min-h-80 flex-col items-center justify-center rounded-card border border-dashed border-line-strong p-10 text-center">
        @include('shell.icon', ['name' => 'peak', 'class' => 'size-10 text-faint', 'stroke' => 1.5])
        <p class="mt-3 font-bold">Choose an entry to see it here</p>
        <p class="hint mt-1">Or press New entry to log something.</p>
    </div>
</div>
