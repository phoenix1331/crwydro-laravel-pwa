{{-- A new version waits for the walker: never reload in the middle of an entry --}}
<div x-data x-show="$store.pwa.needRefresh || $store.pwa.updateRequired" x-cloak x-transition.opacity
     class="fixed inset-x-4 bottom-24 z-40 mx-auto flex max-w-md flex-wrap items-center gap-3 rounded-card bg-panel p-4 text-sm text-panel-ink shadow-xl md:bottom-6 md:left-[calc(15.5rem+1rem)]"
     role="status" aria-live="polite">
    <p class="min-w-0 flex-1 font-bold" x-text="$store.pwa.updateRequired ? $t('update.required') : $t('update.ready')"></p>
    <div class="flex gap-2">
        <button type="button" class="inline-flex min-h-10 items-center rounded-control px-3 font-bold text-panel-muted hover:text-panel-ink"
                x-show="! $store.pwa.updateRequired" @click="$store.pwa.later()" x-text="$t('update.later')">Later</button>
        <button type="button" class="inline-flex min-h-10 items-center rounded-control bg-accent px-4 font-bold text-accent-ink hover:bg-accent-hover"
                x-show="$store.pwa.needRefresh" @click="$store.pwa.update()" x-text="$t('update.button')">Update</button>
    </div>
</div>
