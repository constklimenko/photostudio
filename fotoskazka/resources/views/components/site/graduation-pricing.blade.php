@props([
    'graduationRoots',
    'title' => null,
    'subtitle' => null,
    'content' => null,
    'arPrice' => 500,
])

@if ($graduationRoots->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>
            @if ($content)
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $content }}</p>
            @endif

            @foreach ($graduationRoots as $root)
                @if ($root->children->isNotEmpty())
                    <div class="mt-12" data-graduation-tabs>
                        <div class="flex flex-wrap justify-center gap-3">
                            @foreach ($root->children as $child)
                                <button type="button" data-tab-button="{{ $loop->index }}"
                                        class="graduation-tab {{ $loop->first ? 'is-active' : '' }}">
                                    {{ $child->name }}
                                </button>
                            @endforeach
                        </div>

                        @foreach ($root->children as $child)
                            <div data-tab-panel="{{ $loop->index }}" class="mt-12 {{ $loop->first ? '' : 'hidden' }}">
                                <div class="space-y-10">
                                    @foreach ($child->services as $service)
                                        @php
                                            $servicePhotos = $service->featuredAlbum?->photos ?? collect();
                                        @endphp
                                        <article class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-14 items-center rounded-3xl border border-[#2a2a2a] bg-[#1a1a1a] p-6 sm:p-10">
                                            <div class="order-1">
                                                @if ($servicePhotos->isNotEmpty())
                                                    <div class="album-slider" data-album-slider>
                                                        @foreach ($servicePhotos as $servicePhoto)
                                                            @if ($servicePhoto->media)
                                                                <div>
                                                                    <div class="aspect-[4/3] rounded-xl overflow-hidden bg-black">
                                                                        <img src="{{ $servicePhoto->media->getDisplayUrl() ?: $servicePhoto->media->getUrl() }}"
                                                                             alt="{{ $service->title }}"
                                                                             {{ $loop->first ? 'fetchpriority="high"' : 'loading="lazy"' }}
                                                                             class="w-full h-full object-cover">
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="aspect-[4/3] rounded-xl bg-[#0a0a0a] flex items-center justify-center text-gray-500">
                                                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="order-2 text-center lg:text-left">
                                                <h3 class="font-heading text-2xl sm:text-3xl font-normal tracking-wide text-white">
                                                    <a href="{{ route('services.show', $service->catalogPath()) }}" class="hover:text-[#d4af37] transition">
                                                        {{ $service->title }}
                                                    </a>
                                                </h3>
                                                @if ($service->price_from)
                                                    <div class="mt-4 flex flex-wrap items-center justify-center lg:justify-start gap-3">
                                                        <p class="text-3xl font-bold text-[#d4af37]">Цена: {{ number_format($service->price_from, 0, ',', ' ') }} ₽</p>
                                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 border border-[#d4af37]/60 rounded-full text-[#d4af37] text-sm font-medium" title="Оживающие AR-фото">
                                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                <path d="M15 4V2m-5.5 7.5V7m13-3-1.44 1.44M4.5 4.5 6 6m0 6.5h2m4.5 2.5v2m12 3-4-4m-4-6.5 2-2"/>
                                                                <path d="m15 4 2 2m0 0 4 4M15 4l4 4"/>
                                                            </svg>
                                                            AR + {{ number_format($arPrice, 0, ',', ' ') }} руб.
                                                        </span>
                                                    </div>
                                                @endif
                                                @if ($service->items->isNotEmpty())
                                                    <ul class="mt-6 space-y-3">
                                                        @foreach ($service->items as $item)
                                                            @php $itemIncluded = $item->pivot->is_included ?? true; @endphp
                                                            <li class="flex items-center gap-3 text-left {{ $itemIncluded ? 'text-gray-300' : 'text-gray-500' }}">
                                                                @if ($item->icon)
                                                                    <img src="{{ $item->icon->getUrl() }}" alt="{{ $item->icon->name }}" class="w-5 h-5 shrink-0 object-contain">
                                                                @elseif ($itemIncluded)
                                                                    <svg class="w-5 h-5 shrink-0 text-[#d4af37]" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                @endif
                                                                <span>
                                                                    {{ $item->label }}
                                                                    @if ($item->subtitle)
                                                                        <span class="text-xs opacity-75"> — {{ $item->subtitle }}</span>
                                                                    @endif
                                                                </span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                                                    <a href="#inquiry-form"
                                                       class="inline-flex items-center px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg hover:opacity-90 transition">
                                                        Заказать
                                                    </a>
                                                    <a href="{{ route('services.show', $service->catalogPath()) }}"
                                                       class="inline-flex items-center gap-2 text-[#d4af37] font-medium text-sm uppercase tracking-wider hover:opacity-70 transition">
                                                        Подробнее об услуге
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endif
