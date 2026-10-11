<section x-data="entryForm" x-show="$store.router.is('new')" x-cloak>
    <h1 class="mb-4 text-2xl font-bold">New entry</h1>

    <form class="space-y-5" @submit.prevent="save">
        <div>
            <label for="entry-title" class="label">Title</label>
            <input id="entry-title" type="text" class="field" x-model="title" maxlength="255"
                   placeholder="Welsh poppy by the stream" autocomplete="off" enterkeyhint="next">
        </div>

        <fieldset>
            <legend class="label">Tag</legend>
            <div class="grid grid-cols-3 gap-2">
                <template x-for="option in tags" :key="option.key">
                    <label class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border px-2 text-center text-sm font-semibold transition"
                           :class="tag === option.key ? 'border-moss-600 bg-moss-600 text-white' : 'border-peat-200 bg-white text-peat-800'">
                        <input type="radio" name="tag" class="sr-only" :value="option.key" x-model="tag">
                        <span x-text="option.en"></span>
                    </label>
                </template>
            </div>
        </fieldset>

        <div>
            <label for="entry-notes" class="label">Notes <span class="font-normal text-peat-400">(optional)</span></label>
            <textarea id="entry-notes" rows="4" class="field py-3" x-model="notes" maxlength="10000"
                      placeholder="What did you see?"></textarea>
        </div>

        <p x-show="error" x-text="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"></p>

        <div class="space-y-3">
            <button type="submit" class="btn-primary w-full" :disabled="state !== 'idle'">
                <span x-show="state === 'idle'">Save entry</span>
                <span x-show="state === 'locating'" x-cloak>Getting your location…</span>
                <span x-show="state === 'saving'" x-cloak>Saving…</span>
            </button>

            <button type="button" class="btn-secondary w-full" x-show="state === 'locating'" x-cloak @click="skipLocation()">
                Save without location
            </button>

            <p class="text-center text-sm text-peat-600">Location and time are added automatically. GPS works without signal.</p>
        </div>
    </form>
</section>
