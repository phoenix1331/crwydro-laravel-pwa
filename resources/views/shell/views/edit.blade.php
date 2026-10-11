<section x-data="entryEdit" x-effect="load($store.router.name, $store.router.params.id)" x-show="$store.router.is('edit')" x-cloak
         class="mx-auto max-w-2xl lg:max-w-5xl">
    <a :href="`#/entries/${id}`" class="mb-3 inline-flex min-h-10 items-center gap-1 text-sm font-bold text-accent">
        @include('shell.icon', ['name' => 'back', 'class' => 'size-4', 'stroke' => 2.5])
        <span x-text="$t('form.back')">Back to entry</span>
    </a>

    <h1 class="mb-6 text-3xl" x-text="$t('form.edit_title')">Edit entry</h1>

    <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-10" @submit.prevent="save" x-show="! missing">
        <div class="min-w-0 space-y-6">
            @include('shell.partials.entry-fields', ['prefix' => 'edit'])
        </div>

        <div class="space-y-6 lg:sticky lg:top-8 lg:card lg:p-5">
            @include('shell.partials.entry-photos', ['existing' => true])

            <button type="submit" class="btn-primary w-full" :disabled="saving || resizing" x-text="$t('form.save_changes')">Save changes</button>
        </div>
    </form>

    <p x-show="missing" class="hint" x-text="$t('form.gone')">This entry is no longer on this device.</p>
</section>
