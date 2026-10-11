<div x-data x-show="$store.toast.message" x-cloak x-transition.opacity
     class="fixed inset-x-4 bottom-24 z-30 mx-auto max-w-md rounded-xl px-4 py-3 text-sm font-semibold shadow-lg"
     :class="{
        'bg-peat-900 text-white': $store.toast.tone === 'info',
        'bg-moss-600 text-white': $store.toast.tone === 'success',
        'bg-bracken-500 text-white': $store.toast.tone === 'warning',
        'bg-red-700 text-white': $store.toast.tone === 'error',
     }"
     role="status" aria-live="polite" x-text="$store.toast.message"></div>
