@extends('layouts.site')

@section('title', $page?->seo_title ?: 'Фотосказка — профессиональная фотосъёмка')
@section('meta_description', $page?->seo_description ?: 'Профессиональная фотосъёмка для ваших важных событий. Услуги фотографа, портфолио, выпускные альбомы.')

@section('content')

<section class="hero" id="hero-block">
    <div class="hero-bg">
        @if ($heroImages && $heroImages->isNotEmpty())
            @php
                $cols = $heroImages->chunk(ceil($heroImages->count() / 3));
            @endphp

            <div class="hero-col" data-speed="-0.15">
                @foreach (($cols[0] ?? collect()) as $img)
                    <img src="{{ Storage::url($img->file_path) }}" alt="">
                @endforeach
            </div>

            <div class="hero-col hero-col-center" data-speed="0.1">
                @foreach (($cols[1] ?? collect()) as $img)
                    <img src="{{ Storage::url($img->file_path) }}" alt="">
                @endforeach
            </div>

            <div class="hero-col" data-speed="-0.2">
                @foreach (($cols[2] ?? collect()) as $img)
                    <img src="{{ Storage::url($img->file_path) }}" alt="">
                @endforeach
            </div>
        @endif
    </div>

    <div class="hero-content">
        <div class="hero-text">
            <span class="banner-content">
                <h3 class="font-heading tracking-wide">{{ $page?->title ?: 'ФОТОСКАЗКА УФА' }}</h3>
                <p>{{ $page?->subtitle ?: 'Выпускные альбомы под ключ в Уфе — красиво, вовремя, без стресса' }}</p>
                <a class="btn btn-style mt-sm-5 mt-4" href="#inquiry-form">Узнать больше</a>
            </span>
        </div>
    </div>
</section>

@if ($services->isNotEmpty())
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-gray-900 text-center">{{ $homeSections['services']->home_title ?? 'Наши услуги' }}</h2>
            <p class="mt-3 text-gray-500 text-center">{{ $homeSections['services']->home_subtitle ?? 'Выберите подходящий формат съёмки' }}</p>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($services as $service)
                    <a href="{{ route('services.show', $service->slug) }}" class="group block bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition">
                        @if ($service->cover)
                            <div class="aspect-[4/3] bg-gray-100">
                                <img src="{{ Storage::url($service->cover->thumbnail_path ?? $service->cover->file_path) }}"
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
                            <h3 class="font-heading font-semibold tracking-wide text-gray-900 group-hover:text-amber-600 transition">{{ $service->title }}</h3>
                            @if ($service->short_description)
                                <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $service->short_description }}</p>
                            @endif
                            @if ($service->price_from)
                                <p class="mt-3 font-medium text-amber-600">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($featuredWorks->isNotEmpty())
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-gray-900 text-center">{{ $homeSections['portfolio']->home_title ?? 'Избранные работы' }}</h2>
            <p class="mt-3 text-gray-500 text-center">{{ $homeSections['portfolio']->home_subtitle ?? 'Наши лучшие проекты' }}</p>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($featuredWorks as $album)
                    <a href="{{ route('portfolio.show', $album->slug) }}" class="group block relative overflow-hidden rounded-xl aspect-[4/3] bg-black">
                        @if ($album->cover)
                            <img src="{{ Storage::url($album->cover->thumbnail_path ?? $album->cover->file_path) }}"
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

@if ($testimonials->isNotEmpty())
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-gray-900 text-center">{{ $homeSections['testimonials']->home_title ?? 'Отзывы' }}</h2>
            <p class="mt-3 text-gray-500 text-center">{{ $homeSections['testimonials']->home_subtitle ?? 'Что говорят наши клиенты' }}</p>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($testimonials as $testimonial)
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <div class="flex items-center gap-4 mb-4">
                            @if ($testimonial->photo)
                                <img src="{{ Storage::url($testimonial->photo->thumbnail_path ?? $testimonial->photo->file_path) }}"
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
                                <p class="font-medium text-gray-900">{{ $testimonial->client_name }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $testimonial->content }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($latestPosts->isNotEmpty())
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-gray-900 text-center">{{ $homeSections['blog']->home_title ?? 'Последние статьи' }}</h2>
            <p class="mt-3 text-gray-500 text-center">{{ $homeSections['blog']->home_subtitle ?? 'Полезная информация из мира фотографии' }}</p>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($latestPosts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="group block bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition">
                        @if ($post->cover)
                            <div class="aspect-[16/9] bg-gray-100">
                                <img src="{{ Storage::url($post->cover->thumbnail_path ?? $post->cover->file_path) }}"
                                     alt="{{ $post->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </div>
                        @else
                            <div class="aspect-[16/9] bg-gray-100"></div>
                        @endif
                        <div class="p-5">
                            <p class="text-xs text-gray-400 mb-2">{{ $post->published_at->format('d.m.Y') }}</p>
                            <h3 class="font-heading font-semibold tracking-wide text-gray-900 group-hover:text-amber-600 transition line-clamp-2">{{ $post->title }}</h3>
                            @if ($post->excerpt)
                                <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $post->excerpt }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section id="inquiry-form" class="py-20">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-gray-900 text-center">Оставить заявку</h2>
        <p class="mt-3 text-gray-500 text-center">Заполните форму, и мы свяжемся с вами</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-site.inquiry-form
            :services="$serviceList"
            button-text="Отправить"
        />
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cols = document.querySelectorAll('.hero-bg .hero-col');
    if (!cols.length) return;

    let scrollY = window.scrollY;
    let mouseX = 0;

    function animateHero() {
        scrollY = window.scrollY;
        cols.forEach(col => {
            const speed = parseFloat(col.dataset.speed);
            const y = scrollY * speed;
            const x = (mouseX - window.innerWidth / 3) * speed * 0.01;
            col.style.transform = `translate3d(${x}px, ${y}px, 0)`;
        });
        requestAnimationFrame(animateHero);
    }

    window.addEventListener('mousemove', e => {
        mouseX = e.clientX;
    });

    animateHero();
});
</script>
@endsection
