@props([
    'items',
    'title' => 'Часто задаваемые вопросы',
    'subtitle' => 'Ответы на популярные вопросы',
])

@if ($items->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>

            <div class="mt-12 space-y-0 divide-y divide-[#2a2a2a]" id="faq-accordion">
                @foreach ($items as $item)
                    @php $faqId = 'faq-' . $item->id; @endphp
                    <div class="faq-item">
                        <button type="button" data-faq="{{ $faqId }}"
                                class="faq-toggle w-full flex items-center justify-between py-5 text-left text-white hover:text-[#d4af37] transition">
                            <span class="font-heading text-lg font-normal tracking-wide pr-4">{{ $item->question }}</span>
                            <svg class="faq-icon w-5 h-5 shrink-0 text-gray-400 transition duration-200"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                        <div id="{{ $faqId }}" class="faq-answer hidden pb-5 text-gray-400 leading-relaxed text-sm">
                            {{ $item->answer }}
                        </div>
                    </div>
                @endforeach
            </div>

            <script>
            (function() {
                const container = document.getElementById('faq-accordion');
                if (!container) return;

                container.addEventListener('click', function(e) {
                    const toggle = e.target.closest('.faq-toggle');
                    if (!toggle) return;

                    const answer = document.getElementById(toggle.dataset.faq);
                    const icon = toggle.querySelector('.faq-icon');
                    if (!answer) return;

                    const isOpen = !answer.classList.contains('hidden');
                    answer.classList.toggle('hidden');
                    icon.classList.toggle('rotate-45');
                    toggle.classList.toggle('text-[#d4af37]');
                });
            })();
            </script>
        </div>
    </section>
@endif
