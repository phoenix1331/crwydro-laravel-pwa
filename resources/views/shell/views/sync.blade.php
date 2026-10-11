<section x-data x-show="$store.router.is('sync')" x-cloak class="mx-auto max-w-4xl">
    <h1 class="text-3xl" x-text="$t('sync.title')">Sync</h1>
    <p class="hint mt-1 max-w-[60ch]" x-text="$t('sync.intro')">Entries are saved on this device first and copied to your account whenever there's a signal.</p>

    <dl class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="card p-5">
            <dt class="hint" x-text="$t('sync.on_device_only')">On this device only</dt>
            <dd class="mt-1 text-4xl font-extrabold" :class="$store.sync.unsynced ? 'text-warn' : 'text-ok'" x-text="$store.sync.unsynced"></dd>
        </div>
        <div class="card p-5">
            <dt class="hint" x-text="$t('sync.needing_attention')">Needing attention</dt>
            <dd class="mt-1 text-4xl font-extrabold" :class="$store.sync.failed ? 'text-danger' : 'text-ink'" x-text="$store.sync.failed"></dd>
        </div>
        <div class="card p-5">
            <dt class="hint" x-text="$t('sync.last_synced')">Last synced</dt>
            <dd class="mt-2.5 text-xl font-extrabold"
                x-text="$store.sync.lastSyncAt ? new Intl.RelativeTimeFormat($store.i18n.locale === 'cy' ? 'cy' : 'en-GB', { numeric: 'auto' }).format(Math.round((Date.parse($store.sync.lastSyncAt) - Date.now()) / 60000), 'minute') : $t('sync.never')"></dd>
        </div>
    </dl>

    <p x-show="$store.sync.lastMessage" x-text="$store.sync.lastMessage ? $t($store.sync.lastMessage) : ''" class="notice mt-4 bg-warn-soft text-warn" role="status"></p>

    <p x-show="$store.sync.retryAt" x-cloak class="hint mt-3"
       x-text="$store.sync.retryAt ? $t('sync.retry_at', { time: new Date($store.sync.retryAt).toLocaleTimeString($store.i18n.locale === 'cy' ? 'cy' : 'en-GB', { hour: '2-digit', minute: '2-digit' }) }) : ''"></p>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row" x-show="$store.auth.loggedIn && ! $store.auth.authRequired">
        <button type="button" class="btn-primary sm:min-w-40" :disabled="$store.sync.running" @click="$store.sync.trigger('manual')">
            <span :class="$store.sync.running && 'animate-spin'">@include('shell.icon', ['name' => 'sync', 'class' => 'size-5'])</span>
            <span x-text="$store.sync.running ? $t('sync.running') : $t('sync.now')"></span>
        </button>

        <button type="button" class="btn-secondary" x-show="$store.sync.failed" x-cloak @click="$store.sync.retryFailed()" x-text="$t('sync.retry_failed', { count: $store.sync.failed })"></button>
    </div>

    <a href="#/login" class="btn-primary mt-6 w-full sm:w-auto" x-show="! $store.auth.loggedIn || $store.auth.authRequired" x-cloak
       x-text="$store.auth.authRequired ? $t('sync.log_in_again') : $t('sync.log_in')"></a>

    <div class="card mt-8 grid gap-4 p-5 text-sm sm:grid-cols-2">
        <div>
            <p class="font-bold" x-text="$t('sync.account')">Account</p>
            <p class="hint mt-1" x-show="$store.auth.user" x-text="$t('sync.signed_in_as', { email: $store.auth.user?.email ?? '' })"></p>
            <p class="hint mt-1" x-show="! $store.auth.user" x-text="$t('sync.not_signed_in')">Not signed in. Entries stay on this device until you log in.</p>
        </div>
        <div>
            <p class="font-bold" x-text="$t('sync.when')">When it syncs</p>
            <p class="hint mt-1">
                <span x-text="$t('sync.when_body')">When you open the app, when signal returns and whenever you save.</span>
                <span x-text="$store.sync.backgroundSync ? $t('sync.background_yes') : $t('sync.background_no')"></span>
            </p>
        </div>
    </div>

    <div class="card mt-3 flex items-center justify-between gap-4 p-5 text-sm">
        <div>
            <p class="font-bold" x-text="$t('language.label')">Language</p>
            <p class="hint mt-1">Cymraeg / English</p>
        </div>
        <div class="flex gap-2" role="group" :aria-label="$t('language.label')">
            <button type="button" class="chip" :class="$store.i18n.locale === 'cy' && 'chip-on'" :aria-pressed="$store.i18n.locale === 'cy'" @click="$store.i18n.set('cy')" lang="cy">Cymraeg</button>
            <button type="button" class="chip" :class="$store.i18n.locale === 'en' && 'chip-on'" :aria-pressed="$store.i18n.locale === 'en'" @click="$store.i18n.set('en')" lang="en">English</button>
        </div>
    </div>
</section>
