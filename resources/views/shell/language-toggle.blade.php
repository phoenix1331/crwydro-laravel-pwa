{{-- Shows the flag of the language you would switch to; the accessible name is the language itself --}}
<button type="button" x-data @click="$store.i18n.toggle()"
        class="inline-flex shrink-0 items-center rounded-[4px] p-1 transition-transform hover:scale-105 {{ $class ?? '' }}"
        :aria-label="$t('language.switch_label')" :title="$t('language.switch')" :lang="$store.i18n.locale === 'cy' ? 'en' : 'cy'">
    <img :src="$store.i18n.locale === 'cy' ? '/icons/flags/en.svg' : '/icons/flags/cy.svg'" src="/icons/flags/cy.svg" alt=""
         class="h-[18px] w-[30px] rounded-[2px] object-cover shadow-[0_0_0_1px_rgba(255,255,255,0.35)]" width="30" height="18">
</button>
