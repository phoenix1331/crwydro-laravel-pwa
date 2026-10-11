{{-- Tablet and laptop navigation: the contour panel runs the full height --}}
<aside x-data class="contour-panel sticky top-0 hidden h-dvh flex-col gap-8 px-5 py-6 md:flex" aria-label="Main">
    @include('shell.brand')

    <a href="#/entries/new" class="btn-primary w-full">
        @include('shell.icon', ['name' => 'new', 'class' => 'size-5', 'stroke' => 2.5])
        New entry
    </a>

    <nav class="-mx-2 flex flex-col gap-1">
        @foreach ($navItems as $item)
            <a href="{{ $item['href'] }}" class="flex min-h-11 items-center gap-3 rounded-control px-3 font-bold transition-colors"
               :class="$store.router.is({{ $item['routes'] }}) ? 'bg-panel-line text-panel-ink' : 'text-panel-muted hover:bg-panel-line/60 hover:text-panel-ink'"
               :aria-current="$store.router.is({{ $item['routes'] }}) ? 'page' : null">
                @include('shell.icon', ['name' => $item['icon'], 'class' => 'size-5'])
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="mt-auto rounded-card border border-panel-line bg-panel/80 p-4 text-sm backdrop-blur-sm">
        <p class="flex items-center gap-2 font-bold">
            <span class="size-2.5 rounded-full" :class="$store.sync.unsynced ? 'bg-[#e3a35f]' : 'bg-[#8cc285]'" aria-hidden="true"></span>
            <span x-show="$store.sync.unsynced" x-text="`${$store.sync.unsynced} on this device only`"></span>
            <span x-show="! $store.sync.unsynced">Everything is synced</span>
        </p>
        <p class="mt-1 text-panel-muted" x-show="$store.auth.user" x-text="$store.auth.user?.email"></p>
        <p class="mt-1 text-panel-muted" x-show="! $store.auth.loggedIn">Not logged in, so entries stay on this device.</p>
        <button type="button" x-show="$store.auth.loggedIn && ! $store.auth.authRequired"
                class="mt-3 inline-flex min-h-10 items-center gap-2 rounded-control bg-panel-line px-3 font-bold text-panel-ink hover:bg-panel-muted/30 disabled:opacity-60"
                :disabled="$store.sync.running" @click="$store.sync.trigger('manual')">
            <span :class="$store.sync.running && 'animate-spin'">@include('shell.icon', ['name' => 'sync', 'class' => 'size-4'])</span>
            <span x-text="$store.sync.running ? 'Syncing…' : 'Sync now'"></span>
        </button>
    </div>
</aside>
