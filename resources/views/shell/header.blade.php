{{-- Phone header; from md up the sidebar carries the brand and sync status --}}
<header class="contour-panel sticky top-0 z-20 md:hidden" style="padding-top: env(safe-area-inset-top)">
    <div class="flex h-14 items-center justify-between px-4">
        @include('shell.brand')

        <a href="#/sync" x-data class="flex min-h-10 items-center gap-2 rounded-full px-3 text-sm font-bold"
           :class="$store.sync.unsynced ? 'bg-warn-soft text-warn' : 'bg-panel-line text-panel-ink'"
           :aria-label="$store.sync.unsynced ? `${$store.sync.unsynced} entries not yet synced` : 'All entries synced'">
            <span x-show="$store.sync.running" x-cloak>@include('shell.icon', ['name' => 'spinner', 'class' => 'size-4 animate-spin', 'stroke' => 2.5])</span>
            <span x-show="$store.sync.unsynced" x-text="`${$store.sync.unsynced} on this device`"></span>
            <span x-show="! $store.sync.unsynced" x-cloak>All synced</span>
        </a>
    </div>
</header>
