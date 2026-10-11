{{-- Phone navigation, thumb reach: New sits in the middle --}}
<nav x-data class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-surface/95 backdrop-blur md:hidden"
     style="padding-bottom: env(safe-area-inset-bottom)" aria-label="Main">
    <div class="mx-auto grid h-18 max-w-xl grid-cols-5 items-center">
        @foreach ($navItems as $index => $item)
            @if ($index === 2)
                <a href="#/entries/new" class="-mt-6 flex flex-col items-center gap-1 text-xs font-bold text-ink" aria-label="New entry">
                    <span class="flex size-16 items-center justify-center rounded-full bg-accent text-accent-ink shadow-lg ring-4 ring-canvas active:scale-95">
                        @include('shell.icon', ['name' => 'new', 'class' => 'size-8', 'stroke' => 2.5])
                    </span>
                    New
                </a>
            @endif

            <a href="{{ $item['href'] }}" class="flex flex-col items-center gap-1 py-2 text-xs font-bold"
               :class="$store.router.is({{ $item['routes'] }}) ? 'text-accent' : 'text-faint'"
               :aria-current="$store.router.is({{ $item['routes'] }}) ? 'page' : null">
                @include('shell.icon', ['name' => $item['icon']])
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
