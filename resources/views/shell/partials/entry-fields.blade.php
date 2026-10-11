<div>
    <label for="{{ $prefix }}-title" class="label">Title</label>
    <input id="{{ $prefix }}-title" type="text" class="field" x-model="title" maxlength="255"
           placeholder="Welsh poppy by the stream" autocomplete="off" enterkeyhint="next">
</div>

<fieldset>
    <legend class="label">Tag</legend>
    <div class="grid grid-cols-3 gap-2">
        <template x-for="option in tags" :key="option.key">
            <label class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border px-2 text-center text-sm font-semibold transition"
                   :class="tag === option.key ? 'border-moss-600 bg-moss-600 text-white' : 'border-peat-200 bg-white text-peat-800'">
                <input type="radio" name="{{ $prefix }}-tag" class="sr-only" :value="option.key" x-model="tag">
                <span x-text="option.en"></span>
            </label>
        </template>
    </div>
</fieldset>

<div>
    <label for="{{ $prefix }}-notes" class="label">Notes <span class="font-normal text-peat-400">(optional)</span></label>
    <textarea id="{{ $prefix }}-notes" rows="4" class="field py-3" x-model="notes" maxlength="10000"
              placeholder="What did you see?"></textarea>
</div>

<p x-show="error" x-text="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"></p>
