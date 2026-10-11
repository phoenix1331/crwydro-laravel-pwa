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

<div>
    <p class="label">Photos <span class="font-normal text-peat-400">(up to 4)</span></p>

    <div class="grid grid-cols-4 gap-2">
        @if ($existing ?? false)
            <template x-for="photo in photos" :key="photo.id">
                <div class="relative aspect-square overflow-hidden rounded-xl bg-peat-100">
                    <img :src="photoUrl(photo)" alt="" class="size-full object-cover">
                    <button type="button" x-show="photo.syncStatus !== 'synced'" @click="removePhoto(photo)"
                            class="absolute top-1 right-1 flex size-8 items-center justify-center rounded-full bg-peat-900/80 text-white"
                            aria-label="Remove photo">&times;</button>
                </div>
            </template>
        @endif

        <template x-for="photo in pending" :key="photo.key">
            <div class="relative aspect-square overflow-hidden rounded-xl bg-peat-100">
                <img :src="photo.url" alt="" class="size-full object-cover">
                <button type="button" @click="removePending(photo.key)"
                        class="absolute top-1 right-1 flex size-8 items-center justify-center rounded-full bg-peat-900/80 text-white"
                        aria-label="Remove photo">&times;</button>
            </div>
        </template>

        <div x-show="resizing" x-cloak class="flex aspect-square items-center justify-center rounded-xl bg-peat-100 text-xs font-semibold text-peat-600">
            Resizing…
        </div>
    </div>

    <div class="mt-2 grid grid-cols-2 gap-2" x-show="remaining() > 0">
        <label class="btn-secondary cursor-pointer">
            <input type="file" accept="image/*" capture="environment" class="sr-only" @change="pick">
            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 8h3l2-3h6l2 3h3v11H4z" /><circle cx="12" cy="13" r="3.5" />
            </svg>
            Take photo
        </label>
        <label class="btn-secondary cursor-pointer">
            <input type="file" accept="image/*" multiple class="sr-only" @change="pick">
            Choose
        </label>
    </div>

    <p x-show="photoError" x-text="photoError" class="mt-2 text-sm font-semibold text-red-800"></p>
</div>

<p x-show="error" x-text="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"></p>
