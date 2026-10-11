<div x-data x-show="$store.install.offer" x-cloak class="card mb-4 flex items-start gap-3 p-4">
    <img src="/icons/icon-192.png" alt="" class="size-12 shrink-0 rounded-control" width="48" height="48">
    <div class="min-w-0 flex-1">
        <p class="font-bold" x-text="$t('install.title')">Add Crwydro to your Home Screen</p>
        <p class="hint mt-0.5" x-text="$t('install.body')"></p>
        <p class="mt-2 text-sm font-bold" x-show="$store.install.ios && ! $store.install.deferred" x-text="$t('install.ios')"></p>
        <div class="mt-3 flex gap-2">
            <button type="button" class="btn-primary min-h-10 text-sm" x-show="$store.install.deferred" @click="$store.install.install()" x-text="$t('install.button')">Install</button>
            <button type="button" class="btn-quiet min-h-10 text-sm" @click="$store.install.dismiss()" x-text="$t('install.not_now')">Not now</button>
        </div>
    </div>
</div>
