<section x-data x-show="$store.router.is('sync')" x-cloak class="mx-auto max-w-4xl">
    <h1 class="text-3xl" x-text="$t('sync.title')">Sync</h1>
    <p class="hint mt-1 max-w-[60ch]" x-text="$t('sync.intro')">Entries are saved on this device first and copied to your account whenever there's a signal.</p>

    <dl class="mt-6 grid grid-cols-3 gap-2 sm:gap-3">
        <div class="card p-3 sm:p-5">
            <dt class="hint text-xs leading-snug sm:text-sm" x-text="$t('sync.on_device_only')">On this device only</dt>
            <dd class="mt-1 text-3xl font-extrabold sm:text-4xl" :class="$store.sync.unsynced ? 'text-warn' : 'text-ok'" x-text="$store.sync.unsynced"></dd>
        </div>
        <div class="card p-3 sm:p-5">
            <dt class="hint text-xs leading-snug sm:text-sm" x-text="$t('sync.needing_attention')">Needing attention</dt>
            <dd class="mt-1 text-3xl font-extrabold sm:text-4xl" :class="$store.sync.failed ? 'text-danger' : 'text-ink'" x-text="$store.sync.failed"></dd>
        </div>
        <div class="card p-3 sm:p-5">
            <dt class="hint text-xs leading-snug sm:text-sm" x-text="$t('sync.last_synced')">Last synced</dt>
            <dd class="mt-2 text-base font-extrabold sm:mt-2.5 sm:text-xl"
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

    <div class="card mt-3 p-5 text-sm" x-show="$store.storage.report" x-cloak>
        <p class="font-bold" x-text="$t('storage.title')">Storage on this device</p>

        <template x-if="$store.storage.percent() !== null">
            <div class="mt-3">
                <div class="h-2.5 overflow-hidden rounded-full bg-sunk" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                     :aria-valuenow="$store.storage.percent()" :aria-label="$t('storage.title')">
                    <div class="h-full rounded-full transition-[width]" :class="$store.storage.nearlyFull() ? 'bg-danger' : 'bg-accent'"
                         :style="`width: ${$store.storage.percent()}%`"></div>
                </div>
                <p class="hint mt-2" x-text="$t('storage.used', { used: $store.storage.bytes($store.storage.report.usage), quota: $store.storage.bytes($store.storage.report.quota) })"></p>
            </div>
        </template>
        <p class="hint mt-2" x-show="$store.storage.percent() === null" x-text="$t('storage.used_unknown')"></p>

        <p class="hint mt-1" x-text="$t('storage.entries_photos', { entries: $store.storage.report?.entries ?? 0, photos: $store.storage.report?.photos ?? 0, size: $store.storage.bytes($store.storage.report?.photoBytes ?? 0) })"></p>

        <p x-show="$store.storage.nearlyFull()" class="notice mt-3 bg-danger-soft text-danger" role="alert" x-text="$t('storage.nearly_full')"></p>

        <div class="mt-4 flex items-start gap-3">
            <span class="mt-1 size-2.5 shrink-0 rounded-full" :class="$store.storage.report?.persisted ? 'bg-ok' : 'bg-warn'" aria-hidden="true"></span>
            <div>
                <p x-text="$store.storage.report?.persisted ? $t('storage.persisted') : $t('storage.not_persisted')"></p>
                <p class="hint mt-1" x-show="! $store.storage.report?.persisted && /iPhone|iPad|iPod/.test(navigator.userAgent)" x-text="$t('storage.not_persisted_ios')"></p>
                <button type="button" class="btn-secondary mt-3 min-h-10 text-sm" x-show="! $store.storage.report?.persisted && $store.storage.report?.supported"
                        @click="$store.storage.persist()" x-text="$t('storage.persist')"></button>
            </div>
        </div>

        <div class="mt-4 border-t border-line pt-4" x-show="$store.storage.report?.freeableBytes > 0">
            <button type="button" class="btn-secondary min-h-10 text-sm" @click="$store.storage.free()"
                    x-text="$t('storage.free', { size: $store.storage.bytes($store.storage.report?.freeableBytes ?? 0) })"></button>
            <p class="hint mt-2" x-text="$t('storage.free_hint')"></p>
        </div>
    </div>

    <div class="card mt-3 flex items-center justify-between gap-4 p-5 text-sm">
        <div>
            <p class="font-bold" x-text="$t('language.label')">Language</p>
            <p class="hint mt-1" x-text="$store.i18n.locale === 'cy' ? 'Cymraeg' : 'English'">English</p>
        </div>
        <div class="flex gap-2" role="group" :aria-label="$t('language.label')">
            <button type="button" class="rounded-control p-1.5 ring-2 transition" :class="$store.i18n.locale === 'cy' ? 'ring-accent' : 'ring-transparent hover:ring-line-strong'"
                    :aria-pressed="$store.i18n.locale === 'cy'" @click="$store.i18n.set('cy')" lang="cy" aria-label="Cymraeg" title="Cymraeg">
                <img src="/icons/flags/cy.svg" alt="" class="h-6 w-10 rounded-[3px] object-cover shadow-[0_0_0_1px_var(--line)]" width="40" height="24">
            </button>
            <button type="button" class="rounded-control p-1.5 ring-2 transition" :class="$store.i18n.locale === 'en' ? 'ring-accent' : 'ring-transparent hover:ring-line-strong'"
                    :aria-pressed="$store.i18n.locale === 'en'" @click="$store.i18n.set('en')" lang="en" aria-label="English" title="English">
                <img src="/icons/flags/en.svg" alt="" class="h-6 w-10 rounded-[3px] object-cover shadow-[0_0_0_1px_var(--line)]" width="40" height="24">
            </button>
        </div>
    </div>
</section>
