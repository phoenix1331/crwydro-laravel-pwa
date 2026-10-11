@extends('web.layout', ['scripts' => ['resources/js/web-map.js']])

@section('title', __('app.web.map_title'))

@section('content')
    <div class="min-h-dvh md:grid md:grid-cols-[15.5rem_minmax(0,1fr)]">
        <aside class="brand-panel flex items-center justify-between gap-4 px-4 py-3 md:sticky md:top-0 md:h-dvh md:flex-col md:items-stretch md:justify-start md:gap-8 md:px-5 md:py-6">
            <a href="{{ route('app') }}" class="flex items-center gap-2.5 text-xl font-extrabold tracking-[-0.02em]">
                @include('shell.icon', ['name' => 'peak', 'class' => 'size-7'])
                {{ config('app.name') }}
            </a>

            <div class="hidden flex-col gap-2 md:flex">
                <p class="text-sm font-bold text-panel-muted">{{ __('app.web.download') }}</p>
                <a href="{{ route('export.gpx') }}" class="inline-flex min-h-11 items-center rounded-control bg-panel-line px-3 font-bold hover:bg-panel-muted/30">{{ __('app.web.gpx') }}</a>
                <a href="{{ route('export.csv') }}" class="inline-flex min-h-11 items-center rounded-control bg-panel-line px-3 font-bold hover:bg-panel-muted/30">{{ __('app.web.csv') }}</a>
            </div>

            <div class="flex items-center gap-3 md:mt-auto md:flex-col md:items-stretch md:rounded-card md:border md:border-panel-line md:bg-panel/80 md:p-4 md:text-sm">
                <p class="hidden md:block">
                    <span class="block font-bold">{{ auth()->user()->name }}</span>
                    <span class="text-panel-muted">{{ auth()->user()->email }}</span>
                </p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-10 items-center rounded-control bg-panel-line px-3 text-sm font-bold hover:bg-panel-muted/30">{{ __('app.web.log_out') }}</button>
                </form>
            </div>
        </aside>

        <main class="lg:grid lg:h-dvh lg:grid-cols-[minmax(0,1fr)_24rem]">
            <div class="relative h-[55dvh] lg:h-full">
                <div id="map" class="absolute inset-0 z-0 bg-sunk"></div>
                <div id="map-key" class="absolute bottom-6 left-3 right-3 z-[500] rounded-control bg-surface/95 px-3 py-2 text-xs shadow md:right-auto"></div>
            </div>

            <details class="group bg-surface lg:overflow-y-auto lg:border-l lg:border-line" open>
                <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 border-b border-line px-4 py-3 lg:cursor-default">
                    <span>
                        <span class="block text-xl font-extrabold tracking-[-0.02em]">{{ __('app.web.your_entries') }}</span>
                        <span class="hint">{{ trans_choice('app.web.synced_count', count($entries), ['count' => count($entries)]) }}</span>
                    </span>
                    <span class="text-muted transition-transform group-open:rotate-90 lg:hidden" aria-hidden="true">
                        @include('shell.icon', ['name' => 'back', 'class' => 'size-5 rotate-180'])
                    </span>
                </summary>

                <div class="flex gap-2 border-b border-line p-4 md:hidden">
                    <a href="{{ route('export.gpx') }}" class="btn-secondary min-h-10 flex-1 px-3 text-sm">{{ __('app.web.download_gpx') }}</a>
                    <a href="{{ route('export.csv') }}" class="btn-secondary min-h-10 flex-1 px-3 text-sm">{{ __('app.web.download_csv') }}</a>
                </div>

                @if (count($entries) === 0)
                    <p class="hint p-4">{{ __('app.web.nothing_synced') }}</p>
                @endif

                <ul id="entry-list" class="divide-y divide-line">
                    @foreach ($entries as $entry)
                        <li>
                            <button type="button" data-entry="{{ $entry['id'] }}" class="flex w-full items-center gap-3.5 p-4 text-left transition-colors hover:bg-sunk">
                                @if ($entry['photos'])
                                    <img src="{{ $entry['photos'][0]['thumb'] }}" alt="" class="size-14 shrink-0 rounded-control bg-sunk object-cover" loading="lazy">
                                @else
                                    <span class="flex size-14 shrink-0 items-center justify-center rounded-control bg-sunk text-faint" aria-hidden="true">
                                        @include('shell.icon', ['name' => 'peak', 'class' => 'size-6', 'stroke' => 1.6])
                                    </span>
                                @endif
                                <span class="min-w-0">
                                    <span class="line-clamp-2 font-bold leading-snug">{{ $entry['title'] }}</span>
                                    <span class="mt-0.5 flex flex-wrap gap-x-3 text-sm text-muted">
                                        <span>{{ __('app.tags.'.$entry['tag']->value) }}</span>
                                        <span>{{ \Illuminate\Support\Carbon::parse($entry['recorded_at'])->setTimezone(config('crwydro.display_timezone'))->locale(app()->getLocale())->translatedFormat('j M Y, H:i') }}</span>
                                    </span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </details>
        </main>
    </div>

    <script type="application/json" id="map-data">@json(['entries' => $entries, 'routes' => $routes, 'labels' => $labels])</script>
@endsection
