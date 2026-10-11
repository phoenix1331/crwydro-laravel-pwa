<div>
    <p class="label">Photos <span class="font-normal text-muted">(up to 4)</span></p>

    <div class="grid grid-cols-4 gap-2">
        @if ($existing ?? false)
            <template x-for="photo in photos" :key="photo.id">
                <div class="relative aspect-square overflow-hidden rounded-control bg-sunk">
                    <img :src="photoUrl(photo)" alt="" class="size-full object-cover">
                    <button type="button" x-show="photo.syncStatus !== 'synced'" @click="removePhoto(photo)"
                            class="absolute top-1 right-1 flex size-8 items-center justify-center rounded-full bg-black/70 text-white"
                            aria-label="Remove photo">&times;</button>
                </div>
            </template>
        @endif

        <template x-for="photo in pending" :key="photo.key">
            <div class="relative aspect-square overflow-hidden rounded-control bg-sunk">
                <img :src="photo.url" alt="" class="size-full object-cover">
                <button type="button" @click="removePending(photo.key)"
                        class="absolute top-1 right-1 flex size-8 items-center justify-center rounded-full bg-black/70 text-white"
                        aria-label="Remove photo">&times;</button>
            </div>
        </template>

        <div x-show="resizing" x-cloak class="flex aspect-square items-center justify-center rounded-control bg-sunk text-xs font-bold text-muted">
            Resizing…
        </div>
    </div>

    <div class="mt-2 grid grid-cols-2 gap-2" x-show="remaining() > 0">
        <label class="btn-secondary cursor-pointer has-focus-visible:outline-3 has-focus-visible:outline-accent">
            <input type="file" accept="image/*" capture="environment" class="sr-only" @change="pick">
            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 8h3l2-3h6l2 3h3v11H4z" /><circle cx="12" cy="13" r="3.5" />
            </svg>
            <span class="md:hidden">Take photo</span><span class="hidden md:inline">Camera</span>
        </label>
        <label class="btn-secondary cursor-pointer has-focus-visible:outline-3 has-focus-visible:outline-accent">
            <input type="file" accept="image/*" multiple class="sr-only" @change="pick">
            <span class="md:hidden">Choose</span><span class="hidden md:inline">Choose files</span>
        </label>
    </div>

    <p x-show="photoError" x-text="photoError" class="mt-2 text-sm font-bold text-danger"></p>
</div>

