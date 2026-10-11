{{-- Tablet and laptop navigation: a dark panel the full height of the screen --}}
<aside x-data class="brand-panel sticky top-0 hidden h-dvh flex-col gap-8 px-5 py-6 md:flex" :aria-label="$t('nav.label')" aria-label="Main">
    @include('shell.brand')

    <a href="#/entries/new" class="btn-primary w-full">
        @include('shell.icon', ['name' => 'new', 'class' => 'size-5', 'stroke' => 2.5])
        <span x-text="$t('nav.new_entry')">New entry</span>
    </a>

    <nav class="-mx-2 flex flex-col gap-1">
        @foreach ($navItems as $item)
            <a href="{{ $item['href'] }}" class="flex min-h-11 items-center gap-3 rounded-control px-3 font-bold transition-colors"
               :class="$store.router.is({{ $item['routes'] }}) ? 'bg-panel-line text-panel-ink' : 'text-panel-muted hover:bg-panel-line/60 hover:text-panel-ink'"
               :aria-current="$store.router.is({{ $item['routes'] }}) ? 'page' : null">
                @include('shell.icon', ['name' => $item['icon'], 'class' => 'size-5'])
                <span x-text="$t('{{ $item['key'] }}')">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mt-auto rounded-card border border-panel-line bg-panel/80 p-4 text-sm backdrop-blur-sm">
        <p class="flex items-center gap-2 font-bold">
            <span class="size-2.5 rounded-full" :class="$store.sync.unsynced ? 'bg-[#e3a35f]' : 'bg-[#8cc285]'" aria-hidden="true"></span>
            <span x-show="$store.sync.unsynced" x-text="$t('status.count_on_device_only', { count: $store.sync.unsynced })"></span>
            <span x-show="! $store.sync.unsynced" x-text="$t('status.everything_synced')">Everything is synced</span>
        </p>
        <p class="mt-1 text-panel-muted" x-show="$store.auth.user" x-text="$store.auth.user?.email"></p>
        <p class="mt-1 text-panel-muted" x-show="! $store.auth.loggedIn" x-text="$t('status.not_logged_in')">Not logged in, so entries stay on this device.</p>
        <button type="button" x-show="$store.auth.loggedIn && ! $store.auth.authRequired"
                class="mt-3 inline-flex min-h-10 items-center gap-2 rounded-control bg-panel-line px-3 font-bold text-panel-ink hover:bg-panel-muted/30 disabled:opacity-60"
                :disabled="$store.sync.running" @click="$store.sync.trigger('manual')">
            <span :class="$store.sync.running && 'animate-spin'">@include('shell.icon', ['name' => 'sync', 'class' => 'size-4'])</span>
            <span x-text="$store.sync.running ? $t('sync.running') : $t('sync.now')"></span>
        </button>
    </div>

    <button type="button" class="-mt-4 self-start text-sm font-bold text-panel-muted underline-offset-4 hover:text-panel-ink hover:underline"
            @click="$store.i18n.toggle()" :aria-label="$t('language.switch_label')" x-text="$t('language.switch')">Cymraeg</button>
</aside>
