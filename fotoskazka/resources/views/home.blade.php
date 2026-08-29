@extends('layouts.site')

@section('title', $page?->seo_title ?: 'Фотосказка — профессиональная фотосъёмка')
@section('meta_description', $page?->seo_description ?: 'Профессиональная фотосъёмка для ваших важных событий. Услуги фотографа, портфолио, выпускные альбомы.')

@section('content')

<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-black" id="hero-block">
    @if ($heroImages && $heroImages->isNotEmpty())
        @php $heroBg = $heroImages->first(); @endphp
        <div class="absolute inset-0">
            <img src="{{ $heroBg->getUrl() }}"
                 alt=""
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
            Узнать больше
        </a>
    </div>
</section>

<x-site.social-links variant="section" />

@if ($services->isNotEmpty())
    <section class="py-24" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $homeSections['services']->home_title ?? 'Наши услуги' }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $homeSections['services']->home_subtitle ?? 'Выберите подходящий формат съёмки' }}</p>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($services as $service)
                    <a href="{{ route('services.show', $service->catalogPath()) }}"
                       class="group block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
@if ($service->cover)
                             <div class="aspect-[4/3] bg-gray-100">
                                 <img src="{{ $service->cover->getThumbnailUrl() }}"
                                      alt="{{ $service->title }}"
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
                            <h3 class="font-heading font-semibold tracking-wide text-white group-hover:text-[#d4af37] transition">{{ $service->title }}</h3>
                            @if ($service->short_description)
                                <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $service->short_description }}</p>
                            @endif
                            @if ($service->price_from)
                                <p class="mt-3 font-medium text-[#d4af37]">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</p>
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
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $homeSections['portfolio']->home_title ?? 'Избранные работы' }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $homeSections['portfolio']->home_subtitle ?? 'Наши лучшие проекты' }}</p>

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
    @php
        $horizontalVideos = $videos->where('type', 'horizontal');
        $verticalVideos = $videos->where('type', 'vertical');
    @endphp

    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">Видеогалерея</h2>
            <p class="mt-3 text-gray-400 text-center">Смотрите наши работы в движении</p>

            @if ($horizontalVideos->isNotEmpty())
                <div class="mt-12 max-w-5xl mx-auto space-y-10">
                    @foreach ($horizontalVideos as $video)
                        <div class="aspect-[4/3] rounded-xl overflow-hidden bg-black shadow-lg shadow-black/30"
                             data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                            @if ($video->is_upload)
                                <video class="w-full h-full" controls playsinline preload="metadata">
                                    <source src="{{ $video->source_url }}" type="video/mp4">
                                </video>
                            @else
                                <iframe src="{{ $video->embed_url }}"
                                        title="{{ $video->title }}"
                                        class="w-full h-full"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                        loading="lazy">
                                </iframe>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($verticalVideos->isNotEmpty())
                <div class="mt-12 video-slider" data-video-slider data-aos="fade-up">
                    @foreach ($verticalVideos as $video)
                        <div data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                            <div class="aspect-[9/16] rounded-xl overflow-hidden bg-black shadow-lg shadow-black/30">
                                @if ($video->is_upload)
                                    <video class="w-full h-full object-cover" controls playsinline preload="metadata">
                                        <source src="{{ $video->source_url }}" type="video/mp4">
                                    </video>
                                @else
                                    <iframe src="{{ $video->embed_url }}"
                                            title="{{ $video->title }}"
                                            class="w-full h-full"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen
                                            loading="lazy">
                                    </iframe>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif

@if ($testimonials->isNotEmpty())
    <section class="py-24" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $homeSections['testimonials']->home_title ?? 'Отзывы' }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $homeSections['testimonials']->home_subtitle ?? 'Что говорят наши клиенты' }}</p>

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
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $homeSections['blog']->home_title ?? 'Последние статьи' }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $homeSections['blog']->home_subtitle ?? 'Полезная информация из мира фотографии' }}</p>

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
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">Часто задаваемые вопросы</h2>
            <p class="mt-3 text-gray-400 text-center">Ответы на популярные вопросы</p>

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

<section id="inquiry-form" class="py-24" data-aos="fade-up">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">Оставить заявку</h2>
        <p class="mt-3 text-gray-400 text-center">Заполните форму, и мы свяжемся с вами</p>

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
