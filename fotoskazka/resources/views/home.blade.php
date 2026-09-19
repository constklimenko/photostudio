@extends('layouts.site')

@section('title', $page?->seo_title ?: 'Фотосказка — профессиональная фотосъёмка')
@section('meta_description', $page?->seo_description ?: 'Профессиональная фотосъёмка для ваших важных событий. Услуги фотографа, портфолио, выпускные альбомы.')

@section('content')

@php
    $plainText = function (?string $html): ?string {
        if (! $html) {
            return null;
        }
        $text = trim(strip_tags($html));
        return $text === '' ? null : $text;
    };

    $blockTexts = function (string $slug, string $defaultTitle, string $defaultSubtitle) use ($homeSections, $plainText) {
        $page = $homeSections[$slug] ?? null;
        $content = $plainText($page?->home_content ?: $page?->content);

        return [
            'title' => $page?->home_title ?: $page?->title ?: $defaultTitle,
            'subtitle' => $page?->home_subtitle ?: $page?->subtitle ?: $defaultSubtitle,
            'content' => $content,
        ];
    };

    $servicesBlock = $blockTexts('services', 'Наши услуги', 'Выберите подходящий формат съёмки');
    $graduationAlbumsBlock = $blockTexts('graduation-albums', 'Стоимость альбомов', 'Выберите свою возрастную категорию и комплектацию');
    $shootingBlock = $blockTexts('shooting', 'Фото со съёмок', 'Загляните на съёмочную площадку');
    $portfolioBlock = $blockTexts('portfolio', 'Избранные работы', 'Наши лучшие проекты');
    $videoBlock = $blockTexts('video', 'Видеогалерея', 'Смотрите наши работы в движении');
    $testimonialsBlock = $blockTexts('testimonials', 'Отзывы', 'Что говорят наши клиенты');
    $blogBlock = $blockTexts('blog', 'Последние статьи', 'Полезная информация из мира фотографии');
    $faqBlock = $blockTexts('faq', 'Часто задаваемые вопросы', 'Ответы на популярные вопросы');
    $inquiryBlock = $blockTexts('inquiry', 'Оставить заявку', 'Заполните форму, и мы свяжемся с вами');
@endphp

<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-black" id="hero-block">
    @if ($heroImages && $heroImages->isNotEmpty())
        @php
            $heroBg = $heroImages->first();
            $heroCacheUrl = $heroBg->getDisplayUrl();
            $heroOriginalUrl = $heroBg->getUrl();
        @endphp
        <div class="absolute inset-0">
            <img src="{{ $heroCacheUrl ?: $heroOriginalUrl }}"
                 @if ($heroCacheUrl && $heroOriginalUrl) data-original="{{ $heroOriginalUrl }}" @endif
                 alt=""
                 fetchpriority="high"
                 class="w-full h-full object-cover">
        </div>
    @endif

    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

    <div class="relative z-10 text-center px-4 max-w-4xl mx-auto">
        <h3 class="font-heading tracking-wider text-white text-5xl sm:text-6xl md:text-7xl font-normal mb-6 leading-snug">
            {{ $page?->title ?: 'ФОТОСКАЗКА УФА' }}
        </h3>
        <p class="text-lg sm:text-xl text-gray-300 max-w-2xl mx-auto mb-10 leading-relaxed">
            {{ $page?->subtitle ?: 'Выпускные альбомы под ключ в Уфе — красиво, вовремя, без стресса' }}
        </p>
        <a class="inline-block px-10 py-4 bg-gold text-black font-semibold uppercase tracking-wider text-base rounded-lg shadow-xl hover:opacity-90 transition" href="#inquiry-form">
            Записаться на съёмку
        </a>
    </div>
</section>

<x-site.social-links variant="section" />

@if ($arTeaser['enabled'])
    <x-site.ar-teaser
        :title="$arTeaser['title']"
        :accent="$arTeaser['accent']"
        :subtitle="$arTeaser['subtitle']"
        :footer="$arTeaser['footer']"
        :media="$arTeaser['media']"
    />
@endif

@if ($graduationAlbumsCategories->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $graduationAlbumsBlock['title'] }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $graduationAlbumsBlock['subtitle'] }}</p>
            @if ($graduationAlbumsBlock['content'])
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $graduationAlbumsBlock['content'] }}</p>
            @endif

            @foreach ($graduationAlbumsCategories as $root)
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
                                                            AR + {{ number_format($arTeaser['ar_price'], 0, ',', ' ') }} руб.
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

@if ($homeCategories->isNotEmpty())
    <section class="py-24" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $servicesBlock['title'] }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $servicesBlock['subtitle'] }}</p>
            @if ($servicesBlock['content'])
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $servicesBlock['content'] }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($homeCategories as $category)
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


@if ($shootingWorks->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $shootingBlock['title'] }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $shootingBlock['subtitle'] }}</p>
            @if ($shootingBlock['content'])
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $shootingBlock['content'] }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($shootingWorks as $album)
                    <a href="{{ route('portfolio.show', $album->slug) }}"
                       class="group block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        @if ($album->cover)
                            <div class="aspect-[4/3] bg-gray-100">
                                <img src="{{ $album->cover->getThumbnailUrl() }}"
                                     alt="{{ $album->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                     loading="lazy">
                            </div>
                        @else
                            <div class="aspect-[4/3] bg-gray-100 flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <h3 class="font-heading font-semibold tracking-wide text-white group-hover:text-[#d4af37] transition">{{ $album->title }}</h3>
                            @if ($album->description)
                                <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $album->description }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($featuredWorks->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $portfolioBlock['title'] }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $portfolioBlock['subtitle'] }}</p>
            @if ($portfolioBlock['content'])
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $portfolioBlock['content'] }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($featuredWorks as $album)
                    <a href="{{ route('portfolio.show', $album->slug) }}"
                       class="group block relative overflow-hidden rounded-xl aspect-[4/3] bg-black shadow-lg shadow-black/30"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
@if ($album->cover)
                             <img src="{{ $album->cover->getThumbnailUrl() }}"
                                  alt="{{ $album->title }}"
                                  class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-4">
                            <h3 class="font-heading text-white font-semibold tracking-wide">{{ $album->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($videos->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $videoBlock['title'] }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $videoBlock['subtitle'] }}</p>
            @if ($videoBlock['content'])
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $videoBlock['content'] }}</p>
            @endif

            <x-site.videos :videos="$videos" />
        </div>
    </section>
@endif

@if ($testimonials->isNotEmpty())
    <section class="py-24" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $testimonialsBlock['title'] }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $testimonialsBlock['subtitle'] }}</p>
            @if ($testimonialsBlock['content'])
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $testimonialsBlock['content'] }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($testimonials as $testimonial)
                    <div class="bg-[#1a1a1a] rounded-xl p-6 shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                         data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        <div class="flex items-center gap-4 mb-4">
@if ($testimonial->photo)
                                 <img src="{{ $testimonial->photo->getThumbnailUrl() }}"
                                      alt="{{ $testimonial->client_name }}"
                                      class="w-12 h-12 rounded-full object-cover bg-gray-100">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <p class="font-medium text-white">{{ $testimonial->client_name }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-400 leading-relaxed">{{ $testimonial->content }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($latestPosts->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $blogBlock['title'] }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $blogBlock['subtitle'] }}</p>
            @if ($blogBlock['content'])
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $blogBlock['content'] }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($latestPosts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}"
                       class="group block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
@if ($post->cover)
                             <div class="aspect-[16/9] bg-gray-100">
                                 <img src="{{ $post->cover->getThumbnailUrl() }}"
                                      alt="{{ $post->title }}"
                                      class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                             </div>
                        @else
                            <div class="aspect-[16/9] bg-gray-100"></div>
                        @endif
                        <div class="p-5">
                            <p class="text-xs text-gray-400 mb-2">{{ $post->published_at->format('d.m.Y') }}</p>
                            <h3 class="font-heading font-semibold tracking-wide text-white group-hover:text-[#d4af37] transition line-clamp-2">{{ $post->title }}</h3>
                            @if ($post->excerpt)
                                <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $post->excerpt }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($faqItems->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $faqBlock['title'] }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $faqBlock['subtitle'] }}</p>

            <div class="mt-12 space-y-0 divide-y divide-[#2a2a2a]" id="faq-accordion">
                @foreach ($faqItems as $item)
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

@if (filled($page?->about_studio_text))
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-base font-semibold text-gray-700 mb-4">{{ $page->about_studio_title ?: 'О студии' }}</h2>
        <div class="text-sm text-gray-500 leading-relaxed">
            {!! $page->about_studio_text !!}
        </div>
    </section>
@endif

<section id="inquiry-form" class="py-24" data-aos="fade-up">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $inquiryBlock['title'] }}</h2>
        <p class="mt-3 text-gray-400 text-center">{{ $inquiryBlock['subtitle'] }}</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-900/30 border border-green-800 text-green-400 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-site.inquiry-form
            :services="$serviceList"
            button-text="Отправить"
        />
    </div>
</section>


@endsection
