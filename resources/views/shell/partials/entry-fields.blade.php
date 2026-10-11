<div>
    <label for="{{ $prefix }}-route" class="label"><span x-text="$t('form.walk')">Walk</span> <span class="font-normal text-muted" x-text="$t('optional')">(optional)</span></label>
    <select id="{{ $prefix }}-route" class="field" x-model="routeId">
        <option value="" x-text="$t('form.not_on_walk')">Not on a featured walk</option>
        <template x-for="walk in walks" :key="walk.id">
            <option :value="walk.id" x-text="walkLabel(walk.id)" :selected="walk.id === routeId"></option>
        </template>
    </select>
</div>

<div>
    <p class="label" x-text="$t('form.look_out')">Look out for</p>
    <div class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:mx-0 lg:flex-wrap lg:px-0">
        <template x-for="item in quickPicks()" :key="item.cy">
            <button type="button" @click="pickSpecies(item)"
                    class="chip px-3.5">
                <span class="font-bold" x-text="item.cy"></span>
                <span class="font-normal text-muted" x-text="`(${item.en})`"></span>
            </button>
        </template>
    </div>
    <p x-show="hint" x-text="hint" class="notice mt-2 bg-warn-soft text-warn"></p>
</div>

<div>
    <label for="{{ $prefix }}-title" class="label" x-text="$t('form.title')">Title</label>
    <input id="{{ $prefix }}-title" type="text" class="field" x-model="title" maxlength="255"
           :placeholder="$t('form.title_placeholder')" placeholder="Welsh poppy by the stream" autocomplete="off" enterkeyhint="next">
</div>

<fieldset>
    <legend class="label" x-text="$t('form.tag')">Tag</legend>
    <div class="grid grid-cols-3 gap-2">
        <template x-for="option in tags" :key="option.key">
            <label class="chip min-h-12 cursor-pointer justify-center rounded-control px-2 has-focus-visible:outline-3 has-focus-visible:outline-accent"
                   :class="tag === option.key && 'chip-on'">
                <input type="radio" name="{{ $prefix }}-tag" class="sr-only" :value="option.key" x-model="tag">
                <span x-text="option[$store.i18n.locale]"></span>
            </label>
        </template>
    </div>
</fieldset>

<div>
    <label for="{{ $prefix }}-notes" class="label"><span x-text="$t('form.notes')">Notes</span> <span class="font-normal text-muted" x-text="$t('optional')">(optional)</span></label>
    <textarea id="{{ $prefix }}-notes" rows="4" class="field py-3" x-model="notes" maxlength="10000"
              :placeholder="$t('form.notes_placeholder')" placeholder="What did you see?"></textarea>
</div>

<p x-show="error" x-text="error" class="notice bg-danger-soft text-danger" role="alert"></p>
