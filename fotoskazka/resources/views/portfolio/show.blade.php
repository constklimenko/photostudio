@extends('layouts.site')

@section('title', $album->title . ' — Портфолио — Фотосказка')
@section('meta_description', $album->seo_description ?: $album->description)

@section('content')

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-sm text-gray-500 mb-8">
            <a href="{{ route('home') }}" class="hover:text-amber-600 transition">Главная</a>
            <span class="mx-2">/</span>
            <a href="{{ route('portfolio.index') }}" class="hover:text-amber-600 transition">Портфолио</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900">{{ $album->title }}</span>
        </nav>

        <div class="max-w-3xl mx-auto text-center mb-12">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $album->title }}</h1>
            @if ($album->description)
                <p class="mt-4 text-lg text-gray-600">{{ $album->description }}</p>
            @endif
        </div>

        @if ($album->photos->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="portfolioGrid">
                @foreach ($album->photos as $photo)
                    <a href="{{ Storage::url($photo->media->file_path) }}"
                       class="rounded-xl overflow-hidden bg-gray-100 block cursor-pointer group lightbox-trigger"
                       data-index="{{ $loop->index }}">
                        <img src="{{ Storage::url($photo->media->file_path) }}"
                             alt="{{ $photo->caption ?? $album->title }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                             loading="lazy">
                    </a>
                @endforeach
            </div>

            <div id="lightbox" class="fixed inset-0 z-50 hidden bg-black/90"
                 style="backdrop-filter: blur(4px);">
                <button id="lightboxClose" class="fixed top-4 right-4 text-white/70 hover:text-white text-4xl leading-none z-10 w-12 h-12 flex items-center justify-center">&times;</button>
                <button id="lightboxPrev" class="fixed left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-5xl leading-none z-10 w-12 h-12 flex items-center justify-center">&lsaquo;</button>

                <img id="lightboxImage" src="" alt=""
                     class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 max-h-[90vh] max-w-[90vw] object-contain rounded-lg shadow-2xl select-none">

                <button id="lightboxNext" class="fixed right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-5xl leading-none z-10 w-12 h-12 flex items-center justify-center">&rsaquo;</button>
            </div>

            <script>
            (function() {
                const triggers = document.querySelectorAll('.lightbox-trigger');
                if (!triggers.length) return;

                const lightbox = document.getElementById('lightbox');
                const lightboxImage = document.getElementById('lightboxImage');
                const closeBtn = document.getElementById('lightboxClose');
                const prevBtn = document.getElementById('lightboxPrev');
                const nextBtn = document.getElementById('lightboxNext');

                const images = Array.from(triggers).map(t => ({
                    src: t.getAttribute('href'),
                    alt: t.querySelector('img').getAttribute('alt'),
                }));

                let currentIndex = 0;

                function open(index) {
                    currentIndex = index;
                    showImage();
                    lightbox.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }

                function close() {
                    lightbox.classList.add('hidden');
                    document.body.style.overflow = '';
                }

                function showImage() {
                    const img = images[currentIndex];
                    lightboxImage.src = img.src;
                    lightboxImage.alt = img.alt;
                }

                function prev() {
                    currentIndex = (currentIndex - 1 + images.length) % images.length;
                    showImage();
                }

                function next() {
                    currentIndex = (currentIndex + 1) % images.length;
                    showImage();
                }

                triggers.forEach(t => {
                    t.addEventListener('click', function(e) {
                        e.preventDefault();
                        open(parseInt(this.dataset.index));
                    });
                });

                closeBtn.addEventListener('click', close);
                lightbox.addEventListener('click', function(e) {
                    if (e.target === lightbox) close();
                });
                prevBtn.addEventListener('click', prev);
                nextBtn.addEventListener('click', next);

                document.addEventListener('keydown', function(e) {
                    if (lightbox.classList.contains('hidden')) return;
                    if (e.key === 'Escape') close();
                    if (e.key === 'ArrowLeft') prev();
                    if (e.key === 'ArrowRight') next();
                });
            })();
            </script>
        @else
            <div class="text-center py-20 text-gray-500">
                <p>В альбоме пока нет фотографий.</p>
            </div>
        @endif
    </div>
</section>

@if ($album->services->isNotEmpty())
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">Эта съёмка доступна в&nbsp;услугах</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-4xl mx-auto">
                @foreach ($album->services as $service)
                    <a href="{{ route('services.show', $service->slug) }}"
                       class="block bg-white rounded-xl border border-gray-100 hover:shadow-lg transition overflow-hidden">
                        @if ($service->cover)
                            <div class="aspect-[4/3] bg-gray-100">
                                <img src="{{ Storage::url($service->cover->thumbnail_path ?? $service->cover->file_path) }}"
                                     alt="{{ $service->title }}"
                                     class="w-full h-full object-cover"
                                     loading="lazy">
                            </div>
                        @endif
                        <div class="p-4 text-center">
                            <h3 class="font-semibold text-gray-900 hover:text-amber-600 transition">{{ $service->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section id="inquiry-form" class="py-20 {{ $album->services->isNotEmpty() ? 'bg-white' : 'bg-gray-50' }}">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center">Записаться на съёмку</h2>
        <p class="mt-3 text-gray-500 text-center">Заполните форму, и мы свяжемся с вами</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-site.inquiry-form
            :services="$services"
            :selected-service-id="$album->services->first()?->id"
        />
    </div>
</section>

@endsection
