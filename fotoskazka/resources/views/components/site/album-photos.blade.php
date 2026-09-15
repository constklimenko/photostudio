@props(['album', 'photos' => null, 'comments' => false])

@php
    $galleryPhotos = $photos ?? $album->photos;
    $commenter = auth()->user();
    $canCommentOnPhotos = $comments && $commenter && $galleryPhotos->isNotEmpty()
        && $commenter->can('create', [\App\Models\Comment::class, $galleryPhotos->first()]);
@endphp

@if ($galleryPhotos->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="portfolioGrid">
        @foreach ($galleryPhotos as $photo)
            <a href="{{ $photo->media->getLightboxUrl() }}"
               class="rounded-xl overflow-hidden bg-[#1a1a1a] block cursor-pointer group lightbox-trigger shadow-lg shadow-black/30 hover:bg-[#242424] transition"
               data-index="{{ $loop->index }}"
               @if ($comments) data-photo-id="{{ $photo->id }}" data-comments="{{ $photo->comments->count() }}" @endif
               data-original="{{ $photo->media->getUrl() }}"
               data-display="{{ $photo->media->getDisplayUrl() }}"
               data-caption="{{ $photo->caption }}"
               data-aos="{{ $loop->even ? 'flip-left' : 'flip-right' }}">
                 <img src="{{ $photo->media->getDisplayUrl() }}"
                      alt="{{ $photo->caption ?? $album->title }}"
                      class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                      loading="lazy">
                 @if ($comments && $photo->comments->isNotEmpty())
                     <span class="absolute bottom-2 right-2 z-[1] flex items-center gap-1 rounded-full bg-black/60 px-2 py-1 text-xs text-white/90">
                         <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                             <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-3.727-.81l-3.384.937a1 1 0 01-.982-1.361l1.15-3.201A7.02 7.02 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7zm-9-3a1 1 0 112 0v2h2a1 1 0 110 2h-2v2a1 1 0 11-2 0v-2H7a1 1 0 110-2h2V7z" clip-rule="evenodd"/>
                         </svg>
                         {{ $photo->comments->count() }}
                     </span>
                 @endif
            </a>
        @endforeach
    </div>

    @if ($comments)
        @foreach ($galleryPhotos as $photo)
            <div id="photo-comments-{{ $photo->id }}" class="hidden">
                <div class="max-h-[45vh] space-y-4 overflow-y-auto pr-2">
                    @forelse ($photo->comments as $comment)
                        <div>
                            <div class="flex items-center justify-between gap-4 text-sm text-gray-500">
                                <span class="font-medium text-gray-300">{{ $comment->user->name }}</span>
                                <span class="shrink-0">{{ $comment->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-300">{!! nl2br(e($comment->body)) !!}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Комментариев пока нет</p>
                    @endforelse
                </div>

                @if ($canCommentOnPhotos)
                    <form method="POST" action="{{ route('cabinet.photo.comments.store', $photo) }}" class="mt-4" novalidate>
                        @csrf
                        <label for="photo-comment-{{ $photo->id }}" class="block text-sm font-medium text-gray-400">Ваш комментарий</label>
                        <textarea id="photo-comment-{{ $photo->id }}" name="body" rows="2" required
                                  class="mt-2 w-full rounded-lg border border-white/10 bg-black/40 p-3 text-sm text-white focus:border-[#d4af37]/50 focus:outline-none">{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                class="mt-3 inline-flex items-center gap-2 rounded-lg bg-[#d4af37] px-4 py-2 text-sm font-semibold text-black hover:bg-[#e3c25e] transition">
                            Оставить комментарий
                        </button>
                    </form>
                @endif
            </div>
        @endforeach
    @endif

    <div id="lightbox" class="fixed inset-0 z-50 hidden bg-black/90"
         style="backdrop-filter: blur(4px);">
        <button id="lightboxClose" class="fixed top-4 right-4 text-white/70 hover:text-white text-4xl leading-none z-10 w-12 h-12 flex items-center justify-center">&times;</button>
        <button id="lightboxPrev" class="fixed left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-5xl leading-none z-10 w-12 h-12 flex items-center justify-center">&lsaquo;</button>

        <div class="absolute inset-0 flex flex-col items-center justify-center px-4 pt-20 pb-32 z-[3]">
            <img id="lightboxImage" src="" alt=""
                 class="max-h-[46vh] max-w-[92vw] object-contain rounded-lg shadow-2xl select-none">

            <p id="lightboxCaption"
               class="hidden mt-4 max-w-[min(80vw,40rem)] text-center text-sm text-gray-300">
            </p>
        </div>

        @if ($comments)
            <div id="lightboxComments"
                 class="fixed bottom-16 left-1/2 -translate-x-1/2 z-[4] w-[min(92vw,44rem)] pointer-events-auto">
                <div id="lightboxCommentsBody"
                     class="hidden mb-2 rounded-xl border border-white/10 bg-[#0e0e0e]/95 p-5 backdrop-blur"></div>
                <button id="lightboxCommentsToggle" type="button"
                        class="flex w-full items-center justify-between gap-2 rounded-xl border border-white/10 bg-[#0e0e0e]/95 px-4 py-2.5 text-sm text-gray-300 backdrop-blur transition hover:border-[#d4af37]/40">
                    <span class="font-medium">Комментарии <span id="lightboxCommentsCount"></span></span>
                    <svg id="lightboxCommentsChevron" class="h-4 w-4 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        @endif

        <button id="lightboxNext" class="fixed right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white text-5xl leading-none z-10 w-12 h-12 flex items-center justify-center">&rsaquo;</button>

@auth
        <a id="lightboxDownload" href="#"
           class="fixed bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 px-4 py-2 text-sm text-white/70 hover:text-[#d4af37] border border-white/20 hover:border-[#d4af37] rounded-lg transition z-10"
           download>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Скачать в оригинальном разрешении</span>
        </a>
@endauth

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
        const commentsBody = document.getElementById('lightboxCommentsBody');
        const commentsToggle = document.getElementById('lightboxCommentsToggle');
        const commentsCount = document.getElementById('lightboxCommentsCount');
        const commentsChevron = document.getElementById('lightboxCommentsChevron');
        const commentsExpanded = () => commentsBody && !commentsBody.classList.contains('hidden');

        if (lightbox && lightbox.parentElement !== document.body) {
            document.body.appendChild(lightbox);
        }

        const images = Array.from(triggers).map(t => ({
            src: t.getAttribute('href'),
            original: t.getAttribute('data-original'),
            display: t.getAttribute('data-display'),
            caption: t.getAttribute('data-caption') || '',
            photoId: t.getAttribute('data-photo-id'),
            comments: parseInt(t.getAttribute('data-comments') || '0', 10),
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

            if (commentsBody) {
                const block = img.photoId ? document.getElementById('photo-comments-' + img.photoId) : null;
                if (block) {
                    if (commentsBody.innerHTML !== block.innerHTML) {
                        commentsBody.innerHTML = block.innerHTML;
                    }
                    if (commentsCount) {
                        commentsCount.textContent = `(${img.comments})`;
                    }
                    if (commentsToggle) {
                        commentsToggle.classList.remove('hidden');
                    }
                } else {
                    commentsBody.innerHTML = '';
                    commentsCount.textContent = '';
                    if (commentsToggle) {
                        commentsToggle.classList.add('hidden');
                    }
                }
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

        if (commentsToggle) {
            commentsToggle.addEventListener('click', function() {
                const wasExpanded = commentsExpanded();
                commentsBody.classList.toggle('hidden', wasExpanded);
                commentsChevron.classList.toggle('rotate-180', !wasExpanded);
            });
        }

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

        const snapshot = (window.location.hash || '').match(/#photo-(\d+)/);
        if (snapshot) {
            const idx = images.findIndex(i => i.photoId === snapshot[1]);
            if (idx !== -1) open(idx);
        }
    })();
    </script>
@endif