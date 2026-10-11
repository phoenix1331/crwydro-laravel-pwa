<section x-data x-show="$store.router.is('sync')" x-cloak>
    <h1 class="mb-4 text-2xl font-bold">Sync</h1>

    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-white p-4 shadow-sm">
            <p class="text-sm text-peat-600">Not yet synced</p>
            <p class="mt-1 text-3xl font-bold" :class="$store.sync.unsynced ? 'text-bracken-600' : 'text-moss-600'" x-text="$store.sync.unsynced"></p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm">
            <p class="text-sm text-peat-600">Last synced</p>
            <p class="mt-2 text-lg font-semibold"
               x-text="$store.sync.lastSyncAt ? new Intl.RelativeTimeFormat('en-GB', { numeric: 'auto' }).format(Math.round((Date.parse($store.sync.lastSyncAt) - Date.now()) / 60000), 'minute') : 'Never'"></p>
        </div>
    </div>

    <p x-show="$store.sync.lastMessage" x-text="$store.sync.lastMessage"
       class="mt-4 rounded-xl bg-bracken-400/20 px-4 py-3 text-sm font-semibold text-bracken-600" role="status"></p>

    <p x-show="$store.sync.retryAt" x-cloak class="mt-3 text-sm text-peat-600">
        Next automatic retry at <span class="font-semibold" x-text="$store.sync.retryAt ? new Date($store.sync.retryAt).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) : ''"></span>.
    </p>

    <div class="mt-5 space-y-3" x-show="$store.auth.loggedIn && ! $store.auth.authRequired">
        <button type="button" class="btn-primary w-full" :disabled="$store.sync.running" @click="$store.sync.trigger('manual')">
            <span x-text="$store.sync.running ? 'Syncing…' : 'Sync now'"></span>
        </button>

        <button type="button" class="btn-secondary w-full" x-show="$store.sync.failed" x-cloak @click="$store.sync.retryFailed()">
            Retry <span x-text="$store.sync.failed"></span> failed
        </button>
    </div>

    <a href="#/login" class="btn-primary mt-5 w-full" x-show="! $store.auth.loggedIn || $store.auth.authRequired" x-cloak
       x-text="$store.auth.authRequired ? 'Log in again' : 'Log in to sync'"></a>

    <div class="mt-8 rounded-2xl bg-white p-4 text-sm text-peat-600 shadow-sm">
        <p x-show="$store.auth.user" x-cloak>Signed in as <span class="font-semibold text-peat-900" x-text="$store.auth.user?.email"></span>.</p>
        <p class="mt-2">Crwydro syncs when you open it, when signal returns and whenever you save.
            <span x-show="$store.sync.backgroundSync">This browser can also sync in the background after you close the app.</span>
            <span x-show="! $store.sync.backgroundSync">Keep the app open for a moment when you get signal so it can finish syncing.</span>
        </p>
    </div>
</section>
