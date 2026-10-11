<section x-data="entryEdit" x-effect="load($store.router.name, $store.router.params.id)" x-show="$store.router.is('edit')" x-cloak>
    <a :href="`#/entries/${id}`" class="mb-3 inline-flex min-h-10 items-center text-sm font-semibold text-moss-600">&larr; Back</a>

    <h1 class="mb-4 text-2xl font-bold">Edit entry</h1>

    <form class="space-y-5" @submit.prevent="save" x-show="! missing">
        @include('shell.partials.entry-fields', ['prefix' => 'edit', 'existing' => true])

        <button type="submit" class="btn-primary w-full" :disabled="saving || resizing">Save changes</button>
    </form>

    <p x-show="missing" class="text-peat-600">This entry no longer exists on this device.</p>
</section>
