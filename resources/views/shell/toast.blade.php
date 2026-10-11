<div x-data x-show="$store.toast.message" x-cloak x-transition.opacity
     class="fixed inset-x-4 bottom-24 z-30 mx-auto flex max-w-md items-start gap-3 rounded-card bg-panel px-4 py-3 text-sm font-bold text-panel-ink shadow-lg md:bottom-6 md:left-[calc(15.5rem+1rem)]"
     role="status" aria-live="polite">
    <span class="mt-1 size-2.5 shrink-0 rounded-full"
          :class="{
            'bg-panel-muted': $store.toast.tone === 'info',
            'bg-[#8cc285]': $store.toast.tone === 'success',
            'bg-[#e3a35f]': $store.toast.tone === 'warning',
            'bg-[#f08a80]': $store.toast.tone === 'error',
          }" aria-hidden="true"></span>
    <span x-text="$store.toast.message"></span>
</div>
