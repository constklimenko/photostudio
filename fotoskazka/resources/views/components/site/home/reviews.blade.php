@props([
    'photos',
    'title' => 'Отзывы',
    'subtitle' => 'Реальные отзывы наших клиентов',
])

@if ($photos->isNotEmpty())
    <section class="py-24" data-aos="fade-up" data-home-block="reviews">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>

            <div class="mt-12 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4" id="reviewsGrid">
                @foreach ($photos as $photo)
                    @php
                        $photoTitle = $photo->caption ?? $photo->media->title ?? '';
                        $photoAlt = $photoTitle
                            ? $photoTitle . ' — Отзыв Фотосказка Уфа'
                            : 'Отзыв ' . $loop->iteration . ' — Фотосказка | Отзывы клиентов';
                    @endphp
                    <a href="{{ $photo->media->getLightboxUrl() }}"
                       class="rounded-xl overflow-hidden bg-[#1a1a1a] block cursor-pointer group lightbox-trigger shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-index="{{ $loop->index }}"
                       data-original="{{ $photo->media->getUrl() }}"
                       data-display="{{ $photo->media->getDisplayUrl() }}"
                       data-caption="{{ $photo->caption }}"
                       data-aos="{{ $loop->even ? 'flip-left' : 'flip-right' }}">
                        <img src="{{ $photo->media->getDisplayUrl() }}"
                             alt="{{ $photoAlt }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                             loading="lazy">
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <div id="lightbox" class="fixed inset-0 z-50 hidden bg-black/90"
         style="backdrop-filter: blur(4px);">
        <button id="lightboxClose" class="fixed top-4 right-4 text-white/70 hover:text-white text-4xl leading-none z-10 w-12 h-12 flex items-center justify-center">&times;</button>
        <button id="lightboxPrev" class="fixed left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-5xl leading-none z-10 w-12 h-12 flex items-center justify-center">&lsaquo;</button>

        <div class="absolute inset-0 flex flex-col items-center justify-center px-4 pt-20 pb-32 z-[3]">
            <img id="lightboxImage" src="" alt=""
                 class="max-h-[46vh] max-w-[92vw] sm:max-h-[calc(100vh-13rem)] sm:max-w-[calc(100vw-4rem)] object-contain rounded-lg shadow-2xl select-none">

            <p id="lightboxCaption"
               class="hidden mt-4 max-w-[min(80vw,40rem)] text-center text-sm text-gray-300">
            </p>
        </div>

        <button id="lightboxNext" class="fixed right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-5xl leading-none z-10 w-12 h-12 flex items-center justify-center">&rsaquo;</button>

        <button id="lightboxShare"
                class="fixed top-6 left-4 flex items-center gap-2 px-4 py-2 text-sm text-white/70 hover:text-[#d4af37] border border-white/20 hover:border-[#d4af37] rounded-lg transition z-10">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            <span>Поделиться</span>
        </button>
    </div>

    <script>
    (function() {
        const triggers = document.querySelectorAll('#reviewsGrid .lightbox-trigger');
        if (!triggers.length) return;

        const lightbox = document.getElementById('lightbox');
        const lightboxImage = document.getElementById('lightboxImage');
        const closeBtn = document.getElementById('lightboxClose');
        const prevBtn = document.getElementById('lightboxPrev');
        const nextBtn = document.getElementById('lightboxNext');
        const shareBtn = document.getElementById('lightboxShare');
        const shareSpan = shareBtn?.querySelector('span');
        const captionEl = document.getElementById('lightboxCaption');

        if (lightbox && lightbox.parentElement !== document.body) {
            document.body.appendChild(lightbox);
        }

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
                const title = img.alt || 'Отзывы — Фотосказка';

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
