@props([
    'categories',
    'title' => 'Наши услуги',
    'subtitle' => 'Выберите подходящий формат съёмки',
    'content' => null,
])

@if ($categories->isNotEmpty())
    <section class="py-24" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>
            @if ($content)
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $content }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($categories as $category)
                    <a href="{{ route('services.show', $category->catalogPath()) }}"
                       class="group block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        @if ($category->cover)
                            <div class="aspect-[4/3] bg-gray-100">
                                <img src="{{ $category->cover->getThumbnailUrl() }}"
                                     alt="{{ $category->name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </div>
                        @else
                            <div class="aspect-[4/3] bg-gray-100 flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <h3 class="font-heading font-semibold tracking-wide text-white group-hover:text-[#d4af37] transition">{{ $category->name }}</h3>
                            @if (filled(strip_tags((string) $category->description)))
                                <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ Str::limit(strip_tags((string) $category->description), 120) }}</p>
                            @endif
                            <div class="mt-3 flex items-center justify-between">
                                @if ($category->price_from)
                                    <span class="text-sm font-medium text-[#d4af37]">от {{ number_format($category->price_from, 0, ',', ' ') }} ₽</span>
                                @endif
                                <span class="text-sm text-[#d4af37] font-semibold uppercase tracking-wider group-hover:opacity-70 transition">Подробнее</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-12 text-center">
                <a href="{{ route('services.index') }}"
                   class="inline-flex items-center px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg hover:opacity-90 transition">
                    Все услуги
                </a>
            </div>
        </div>
    </section>
@endif
