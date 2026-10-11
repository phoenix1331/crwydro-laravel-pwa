<section x-data="entryForm" x-show="$store.router.is('new')" x-cloak class="mx-auto max-w-2xl lg:max-w-5xl">
    <h1 class="mb-6 text-3xl">New entry</h1>

    <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-10" @submit.prevent="save">
        <div class="min-w-0 space-y-6">
            @include('shell.partials.entry-fields', ['prefix' => 'entry'])
        </div>

        <div class="space-y-6 lg:sticky lg:top-8 lg:card lg:p-5">
            @include('shell.partials.entry-photos', ['existing' => false])

            <div class="space-y-3">
                <button type="submit" class="btn-primary w-full" :disabled="state !== 'idle' || resizing">
                    <span x-show="state === 'idle'">Save entry</span>
                    <span x-show="state === 'locating'" x-cloak>Getting your location…</span>
                    <span x-show="state === 'saving'" x-cloak>Saving…</span>
                </button>

                <button type="button" class="btn-secondary w-full" x-show="state === 'locating'" x-cloak @click="skipLocation()">
                    Save without location
                </button>

                <p class="hint text-center">Location and time are added when you save. GPS works without signal.</p>
            </div>
        </div>
    </form>
</section>
