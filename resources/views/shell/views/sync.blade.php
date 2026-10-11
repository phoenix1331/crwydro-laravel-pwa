<section x-data x-show="$store.router.is('sync')" x-cloak class="mx-auto max-w-4xl">
    <h1 class="text-3xl">Sync</h1>
    <p class="hint mt-1 max-w-[60ch]">Entries are saved on this device first and copied to your account whenever there's a signal.</p>

    <dl class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="card p-5">
            <dt class="hint">On this device only</dt>
            <dd class="mt-1 text-4xl font-extrabold" :class="$store.sync.unsynced ? 'text-warn' : 'text-ok'" x-text="$store.sync.unsynced"></dd>
        </div>
        <div class="card p-5">
            <dt class="hint">Needing attention</dt>
            <dd class="mt-1 text-4xl font-extrabold" :class="$store.sync.failed ? 'text-danger' : 'text-ink'" x-text="$store.sync.failed"></dd>
        </div>
        <div class="card p-5">
            <dt class="hint">Last synced</dt>
            <dd class="mt-2.5 text-xl font-extrabold"
                x-text="$store.sync.lastSyncAt ? new Intl.RelativeTimeFormat('en-GB', { numeric: 'auto' }).format(Math.round((Date.parse($store.sync.lastSyncAt) - Date.now()) / 60000), 'minute') : 'Never'"></dd>
        </div>
    </dl>

    <p x-show="$store.sync.lastMessage" x-text="$store.sync.lastMessage" class="notice mt-4 bg-warn-soft text-warn" role="status"></p>

    <p x-show="$store.sync.retryAt" x-cloak class="hint mt-3">
        Trying again automatically at <span class="font-bold text-ink" x-text="$store.sync.retryAt ? new Date($store.sync.retryAt).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) : ''"></span>.
    </p>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row" x-show="$store.auth.loggedIn && ! $store.auth.authRequired">
        <button type="button" class="btn-primary sm:min-w-40" :disabled="$store.sync.running" @click="$store.sync.trigger('manual')">
            <span :class="$store.sync.running && 'animate-spin'">@include('shell.icon', ['name' => 'sync', 'class' => 'size-5'])</span>
            <span x-text="$store.sync.running ? 'Syncing…' : 'Sync now'"></span>
        </button>

        <button type="button" class="btn-secondary" x-show="$store.sync.failed" x-cloak @click="$store.sync.retryFailed()">
            Retry <span x-text="$store.sync.failed"></span> needing attention
        </button>
    </div>

    <a href="#/login" class="btn-primary mt-6 w-full sm:w-auto" x-show="! $store.auth.loggedIn || $store.auth.authRequired" x-cloak
       x-text="$store.auth.authRequired ? 'Log in again' : 'Log in to sync'"></a>

    <div class="card mt-8 grid gap-4 p-5 text-sm sm:grid-cols-2">
        <div>
            <p class="font-bold">Account</p>
            <p class="hint mt-1" x-show="$store.auth.user" x-text="`Signed in as ${$store.auth.user?.email}`"></p>
            <p class="hint mt-1" x-show="! $store.auth.user">Not signed in. Entries stay on this device until you log in.</p>
        </div>
        <div>
            <p class="font-bold">When it syncs</p>
            <p class="hint mt-1">When you open the app, when signal returns and whenever you save.
                <span x-show="$store.sync.backgroundSync">This browser can also finish syncing after you close the app.</span>
                <span x-show="! $store.sync.backgroundSync">Keep the app open for a moment when you get signal so it can finish.</span>
            </p>
        </div>
    </div>
</section>
