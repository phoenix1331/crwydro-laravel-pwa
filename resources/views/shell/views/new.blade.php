<section x-data="entryForm" x-show="$store.router.is('new')" x-cloak class="mx-auto max-w-2xl">
    <h1 class="mb-4 text-2xl font-bold">New entry</h1>

    <form class="space-y-5" @submit.prevent="save">
        @include('shell.partials.entry-fields', ['prefix' => 'entry'])

        <div class="space-y-3">
            <button type="submit" class="btn-primary w-full" :disabled="state !== 'idle' || resizing">
                <span x-show="state === 'idle'">Save entry</span>
                <span x-show="state === 'locating'" x-cloak>Getting your location…</span>
                <span x-show="state === 'saving'" x-cloak>Saving…</span>
            </button>

            <button type="button" class="btn-secondary w-full" x-show="state === 'locating'" x-cloak @click="skipLocation()">
                Save without location
            </button>

            <p class="text-center text-sm text-muted">Location and time are added automatically. GPS works without signal.</p>
        </div>
    </form>
</section>
