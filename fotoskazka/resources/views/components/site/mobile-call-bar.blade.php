@php
    $phone = \App\Services\ContactSettings::phone();
    $phoneHref = \App\Services\ContactSettings::phoneHref();
@endphp

<div class="fixed bottom-0 inset-x-0 z-50 md:hidden border-t border-[#1a1a1a] bg-[#0a0a0a]/95 backdrop-blur px-4 py-3 grid grid-cols-2 gap-3">
    @if ($phone && $phoneHref)
        <a href="{{ $phoneHref }}" class="flex items-center justify-center gap-2 py-3 rounded-lg border border-[#d4af37] text-[#d4af37] font-semibold uppercase tracking-wider text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            Позвонить
        </a>
    @endif

    <a href="#" data-open-modal="inquiry" style="text-align: center;" class="{{ $phone && $phoneHref ? '' : 'col-span-2 ' }}flex items-center justify-center py-3 rounded-lg bg-gold text-black font-semibold uppercase tracking-wider text-sm">
        Оставить заявку
    </a>
</div>
