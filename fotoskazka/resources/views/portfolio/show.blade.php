@extends('layouts.site')

@section('title', $album->title . ' — Портфолио — Фотосказка')
@section('meta_description', $album->seo_description ?: $album->description)

@section('content')

<section class="py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-sm text-gray-500 mb-8">
            <a href="{{ route('home') }}" class="hover:text-[#d4af37] transition">Главная</a>
            <span class="mx-2 text-gray-600">/</span>
            <a href="{{ route('portfolio.index') }}" class="hover:text-[#d4af37] transition">Портфолио</a>
            <span class="mx-2 text-gray-600">/</span>
            <span class="text-gray-300">{{ $album->title }}</span>
        </nav>

        <div class="max-w-3xl mx-auto text-center mb-12">
            <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide text-white">{{ $album->title }}</h1>
            @if ($album->description)
                <p class="mt-4 text-lg text-gray-400">{{ $album->description }}</p>
            @endif
            <div class="mt-6 flex justify-center">
                <x-site.share-button :title="$album->title" />
            </div>
        </div>

@if ($album->photos->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="portfolioGrid">
                @foreach ($album->photos as $photo)
                    <a href="{{ $photo->media->getLightboxUrl() }}"
                       class="rounded-xl overflow-hidden bg-[#1a1a1a] block cursor-pointer group lightbox-trigger shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-index="{{ $loop->index }}"
                       data-original="{{ $photo->media->getUrl() }}"
                       data-display="{{ $photo->media->getDisplayUrl() }}"
                       data-caption="{{ $photo->caption }}"
                       data-aos="{{ $loop->even ? 'flip-left' : 'flip-right' }}">
                         <img src="{{ $photo->media->getDisplayUrl() }}"
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

                <p id="lightboxCaption"
                   class="hidden absolute bottom-20 left-1/2 -translate-x-1/2 max-w-[min(80vw,40rem)] text-center text-sm text-gray-300 bg-black/50 px-4 py-2 rounded-lg z-[5]"></p>

                <button id="lightboxNext" class="fixed right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-5xl leading-none z-10 w-12 h-12 flex items-center justify-center">&rsaquo;</button>

@auth
                <a id="lightboxDownload" href="#"
                   class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 px-4 py-2 text-sm text-white/70 hover:text-[#d4af37] border border-white/20 hover:border-[#d4af37] rounded-lg transition z-10"
                   download>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Скачать в оригинальном разрешении</span>
                </a>
@endauth

                <button id="lightboxShare"
                        class="absolute top-6 left-4 flex items-center gap-2 px-4 py-2 text-sm text-white/70 hover:text-[#d4af37] border border-white/20 hover:border-[#d4af37] rounded-lg transition z-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    <span>Поделиться</span>
                </button>
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
                const shareBtn = document.getElementById('lightboxShare');
                const shareSpan = shareBtn?.querySelector('span');
                const downloadBtn = document.getElementById('lightboxDownload');
                const captionEl = document.getElementById('lightboxCaption');

                const images = Array.from(triggers).map(t => ({
                    src: t.getAttribute('href'),
                    original: t.getAttribute('data-original'),
                    display: t.getAttribute('data-display'),
                    caption: t.getAttribute('data-caption') || '',
                    alt: t.querySelector('img').getAttribute('alt'),
                }));

                const smallScreen = window.matchMedia('(max-width: 799px)');

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

                function currentSrc(img) {
                    return smallScreen.matches && img.display ? img.display : img.src;
                }

                function showImage() {
                    const img = images[currentIndex];
                    lightboxImage.src = currentSrc(img);
                    lightboxImage.alt = img.alt;

                    if (captionEl) {
                        captionEl.textContent = img.caption;
                        captionEl.classList.toggle('hidden', !img.caption);
                    }

                    if (downloadBtn) {
                        downloadBtn.setAttribute('href', img.original || img.src);
                        downloadBtn.setAttribute('download', img.original ? '' : 'image.png');
                    }
                }

                smallScreen.addEventListener('change', () => {
                    if (lightbox.classList.contains('hidden')) return;
                    const img = images[currentIndex];
                    const next = currentSrc(img);

                    if (lightboxImage.src !== next) {
                        lightboxImage.src = next;
                    }
                });

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

                if (shareBtn) {
                    shareBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const img = images[currentIndex];
                        const url = img.src;
                        const title = img.alt || '{{ $album->title }}';

                        if (navigator.share) {
                            navigator.share({ title, url }).catch(() => {});
                        } else {
                            navigator.clipboard.writeText(url).then(() => {
                                if (shareSpan) {
                                    shareSpan.textContent = 'Скопировано';
                                    setTimeout(() => { shareSpan.textContent = 'Поделиться'; }, 2000);
                                }
                            });
                        }
                    });
                }

                document.addEventListener('keydown', function(e) {
                    if (lightbox.classList.contains('hidden')) return;
                    if (e.key === 'Escape') close();
                    if (e.key === 'ArrowLeft') prev();
                    if (e.key === 'ArrowRight') next();
                });
            })();
            </script>
        @endif

        <x-site.videos :videos="$album->videos" />

        @if ($album->photos->isEmpty() && $album->videos->isEmpty())
            <div class="text-center py-20 text-gray-500">
                <p>В альбоме пока нет фотографий.</p>
            </div>
        @endif
    </div>
</section>

@if ($album->services->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-2xl font-normal tracking-wide text-white text-center mb-8">Эта съёмка доступна в&nbsp;услугах</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-4xl mx-auto">
                @foreach ($album->services as $service)
                    <a href="{{ route('services.show', $service->slug) }}"
                       class="block bg-[#1a1a1a] rounded-xl shadow-lg shadow-black/30 hover:bg-[#242424] transition overflow-hidden"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
@if ($service->cover)
                             <div class="aspect-[4/3] bg-gray-100">
                                 <img src="{{ $service->cover->getThumbnailUrl() }}"
                                      alt="{{ $service->title }}"
                                      class="w-full h-full object-cover"
                                      loading="lazy">
                             </div>
                        @endif
                        <div class="p-4 text-center">
                            <h3 class="font-heading font-semibold tracking-wide text-white hover:text-[#d4af37] transition">{{ $service->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section id="inquiry-form" class="py-24 {{ $album->services->isNotEmpty() ? '' : 'bg-[#111111]' }}" data-aos="fade-up">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">Записаться на съёмку</h2>
        <p class="mt-3 text-gray-400 text-center">Заполните форму, и мы свяжемся с вами</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-900/30 border border-green-800 text-green-400 rounded-lg text-sm">
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
