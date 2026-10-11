@extends('web.layout', ['scripts' => ['resources/js/web-map.js']])

@section('title', 'Map')

@section('content')
    <main class="grid h-[calc(100dvh-3.5rem)] grid-rows-[1fr_auto] md:grid-cols-[1fr_380px] md:grid-rows-1">
        <div id="map" class="z-0 min-h-[50dvh] bg-peat-100"></div>

        <aside class="overflow-y-auto border-l border-peat-200 bg-white">
            <div class="border-b border-peat-200 p-4">
                <h1 class="text-xl font-bold">Your entries</h1>
                <p class="text-sm text-peat-600">{{ count($entries) }} synced {{ Str::plural('entry', count($entries)) }}</p>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('export.gpx') }}" class="btn-secondary min-h-10 px-4 text-sm">Download GPX</a>
                    <a href="{{ route('export.csv') }}" class="btn-secondary min-h-10 px-4 text-sm">Download CSV</a>
                </div>
            </div>

            @if (count($entries) === 0)
                <p class="p-4 text-peat-600">Nothing synced yet. Entries appear here once your phone has had a signal.</p>
            @endif

            <ul id="entry-list" class="divide-y divide-peat-100">
                @foreach ($entries as $entry)
                    <li>
                        <button type="button" data-entry="{{ $entry['id'] }}" class="flex w-full gap-3 p-4 text-left hover:bg-peat-50">
                            @if ($entry['photos'])
                                <img src="{{ $entry['photos'][0]['thumb'] }}" alt="" class="size-14 shrink-0 rounded-lg object-cover" loading="lazy">
                            @endif
                            <span class="min-w-0">
                                <span class="block truncate font-semibold">{{ $entry['title'] }}</span>
                                <span class="block text-sm text-peat-600">
                                    {{ ucfirst($entry['tag']->value) }} · {{ \Illuminate\Support\Carbon::parse($entry['recorded_at'])->setTimezone(config('crwydro.display_timezone'))->format('j M Y, H:i') }}
                                </span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </aside>
    </main>

    <script type="application/json" id="map-data">@json(['entries' => $entries, 'routes' => $routes])</script>
@endsection
