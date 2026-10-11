<section x-data="entryEdit" x-effect="load($store.router.name, $store.router.params.id)" x-show="$store.router.is('edit')" x-cloak class="mx-auto max-w-2xl">
    <a :href="`#/entries/${id}`" class="mb-3 inline-flex min-h-10 items-center gap-1 text-sm font-bold text-accent"><svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg> Back</a>

    <h1 class="mb-4 text-2xl font-bold">Edit entry</h1>

    <form class="space-y-5" @submit.prevent="save" x-show="! missing">
        @include('shell.partials.entry-fields', ['prefix' => 'edit', 'existing' => true])

        <button type="submit" class="btn-primary w-full" :disabled="saving || resizing">Save changes</button>
    </form>

    <p x-show="missing" class="text-muted">This entry no longer exists on this device.</p>
</section>
