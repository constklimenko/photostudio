import $ from 'jquery';
import 'slick-carousel';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import AOS from 'aos';
import 'aos/dist/aos.css';

document.addEventListener('DOMContentLoaded', () => {
    const heroImage = document.querySelector('#hero-block img[data-original]');

    if (heroImage) {
        const swapToOriginal = () => {
            const original = heroImage.dataset.original;
            if (!original || heroImage.dataset.swapped) return;
            heroImage.dataset.swapped = '1';

            const image = new Image();
            image.onload = () => {
                heroImage.src = original;
            };
            image.src = original;
        };

        if (heroImage.complete) {
            swapToOriginal();
        } else {
            heroImage.addEventListener('load', swapToOriginal, { once: true });
            heroImage.addEventListener('error', swapToOriginal, { once: true });
        }
    }

    AOS.init({
        duration: 600,
        easing: 'ease-out-cubic',
        once: true,
        offset: 60,
    });

    $('[data-video-slider]').each(function () {
        $(this).slick({
            slidesToShow: 3,
            slidesToScroll: 1,
            infinite: false,
            arrows: true,
            dots: false,
            responsive: [
                { breakpoint: 1024, settings: { slidesToShow: 2 } },
                { breakpoint: 640, settings: { slidesToShow: 1 } },
            ],
        });
    });

    const albumLightbox = document.getElementById('albumSliderLightbox');
    const albumLightboxImage = albumLightbox?.querySelector('[data-album-lightbox-image]');
    const albumLightboxCaption = albumLightbox?.querySelector('[data-album-lightbox-caption]');
    let albumLightboxItems = [];
    let albumLightboxIndex = 0;

    if (albumLightbox && albumLightbox.parentElement !== document.body) {
        document.body.appendChild(albumLightbox);
    }

    const showAlbumLightboxImage = () => {
        const item = albumLightboxItems[albumLightboxIndex];
        if (!item || !albumLightboxImage) return;

        albumLightboxImage.src = item.src;
        albumLightboxImage.alt = item.alt;

        if (albumLightboxCaption) {
            albumLightboxCaption.textContent = item.caption;
            albumLightboxCaption.classList.toggle('hidden', !item.caption);
        }
    };

    const openAlbumLightbox = (items, index) => {
        if (!albumLightbox || !items.length) return;

        albumLightboxItems = items;
        albumLightboxIndex = index;
        showAlbumLightboxImage();
        albumLightbox.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    const closeAlbumLightbox = () => {
        if (!albumLightbox) return;
        albumLightbox.classList.add('hidden');
        document.body.style.overflow = '';
    };

    const stepAlbumLightbox = (delta) => {
        if (!albumLightboxItems.length) return;
        albumLightboxIndex = (albumLightboxIndex + delta + albumLightboxItems.length) % albumLightboxItems.length;
        showAlbumLightboxImage();
    };

    albumLightbox?.querySelector('[data-album-lightbox-close]')?.addEventListener('click', closeAlbumLightbox);
    albumLightbox?.querySelector('[data-album-lightbox-prev]')?.addEventListener('click', () => stepAlbumLightbox(-1));
    albumLightbox?.querySelector('[data-album-lightbox-next]')?.addEventListener('click', () => stepAlbumLightbox(1));

    albumLightbox?.addEventListener('click', (e) => {
        if (e.target === albumLightbox) closeAlbumLightbox();
    });

    document.addEventListener('keydown', (e) => {
        if (!albumLightbox || albumLightbox.classList.contains('hidden')) return;
        if (e.key === 'Escape') closeAlbumLightbox();
        if (e.key === 'ArrowLeft') stepAlbumLightbox(-1);
        if (e.key === 'ArrowRight') stepAlbumLightbox(1);
    });

    const initAlbumSlider = ($slider) => {
        if ($slider.hasClass('slick-initialized')) return;

        const slides = [];
        const lightboxItems = [];
        $slider.children().each(function () {
            const $slide = $(this);
            const $img = $slide.find('img').first();
            slides.push($slide.attr('data-slide-thumb') || $img.attr('src') || '');
            lightboxItems.push({
                src: $slide.attr('data-slide-full') || $img.attr('src') || '',
                alt: $img.attr('alt') || '',
                caption: $slide.attr('data-slide-caption') || '',
            });
        });

        $slider.slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            infinite: slides.length > 1,
            arrows: true,
            dots: false,
        });

        const wrapIndex = (index) => ((index % slides.length) + slides.length) % slides.length;
        const currentIndex = () => wrapIndex($slider.slick('slickCurrentSlide') || 0);

        $slider.on('click', '.slick-current img.album-slide-image', (e) => {
            e.preventDefault();
            openAlbumLightbox(lightboxItems, currentIndex());
        });

        const $thumbs = $slider.nextAll('[data-album-thumbs]').first();
        if (slides.length < 2 || $thumbs.length === 0) return;

        const labels = ['Предыдущее фото', 'Текущее фото', 'Следующее фото'];
        const $buttons = labels.map((label, position) => {
            const $button = $('<button>', {
                type: 'button',
                'class': 'album-slider-thumb',
                'aria-label': label,
            });

            $('<img>', { alt: '', loading: 'lazy' }).appendTo($button);
            $button.on('click', () => {
                if (position === 0) {
                    $slider.slick('slickPrev');
                } else if (position === 2) {
                    $slider.slick('slickNext');
                }
            });

            $thumbs.append($button);

            return $button;
        });

        const syncThumbs = () => {
            const current = currentIndex();

            $buttons.forEach(($button, position) => {
                const src = slides[wrapIndex(current + position - 1)];
                const $img = $button.find('img');

                if (src && $img.attr('src') !== src) {
                    $img.attr('src', src);
                }

                $button.toggleClass('is-active', position === 1);

                if (position === 1) {
                    $button.attr('aria-current', 'true');
                } else {
                    $button.removeAttr('aria-current');
                }
            });
        };

        $slider.on('afterChange', syncThumbs).on('setPosition', syncThumbs);
        syncThumbs();
    };

    document.querySelectorAll('[data-graduation-tabs]').forEach((tabs) => {
        const buttons = Array.from(tabs.querySelectorAll('[data-tab-button]'));
        const panels = Array.from(tabs.querySelectorAll('[data-tab-panel]'));

        const syncPanels = (index) => {
            buttons.forEach((btn, i) => btn.classList.toggle('is-active', i === index));
            panels.forEach((panel, i) => {
                const show = i === index;
                panel.classList.toggle('hidden', !show);
                if (!show) return;

                const $sliders = $(panel).find('[data-album-slider]');
                $sliders.each(function () {
                    initAlbumSlider($(this));
                });
                if ($sliders.length) {
                    $sliders.slick('setPosition');
                }
            });
        };

        const initial = panels.findIndex((panel) => !panel.classList.contains('hidden'));
        syncPanels(initial >= 0 ? initial : 0);

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => syncPanels(parseInt(btn.dataset.tabButton, 10)));
        });
    });

    AOS.refresh();

    document.querySelectorAll('video[data-video-forbid-sound]').forEach((video) => {
        const enforceMuted = () => {
            if (!video.muted) video.muted = true;
        };

        video.muted = true;
        video.addEventListener('volumechange', enforceMuted);
        video.addEventListener('play', enforceMuted);
        video.addEventListener('loadedmetadata', enforceMuted);
    });

    document.querySelectorAll('[data-video-player]').forEach((player) => {
        const video = player.querySelector('video');
        if (!video) return;

        const playTrigger = player.querySelector('[data-video-play-trigger]');
        const controls = player.querySelector('[data-video-controls]');
        const toggleBtn = player.querySelector('[data-video-toggle]');
        const togglePauseIcon = player.querySelector('[data-video-toggle-pause-icon]');
        const togglePlayIcon = player.querySelector('[data-video-toggle-play-icon]');
        const progress = player.querySelector('[data-video-progress]');
        const progressFill = player.querySelector('[data-video-progress-fill]');
        const timeEl = player.querySelector('[data-video-time]');

        const formatTime = (seconds) => {
            if (!Number.isFinite(seconds) || seconds < 0) return '0:00';
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return `${m}:${s.toString().padStart(2, '0')}`;
        };

        const showControls = () => {
            if (playTrigger) playTrigger.classList.add('hidden');
            if (controls) controls.classList.remove('hidden');
        };

        const syncState = () => {
            const paused = video.paused || video.ended;
            if (togglePauseIcon) togglePauseIcon.classList.toggle('hidden', paused);
            if (togglePlayIcon) togglePlayIcon.classList.toggle('hidden', !paused);
        };

        const play = () => {
            showControls();
            video.play();
            syncState();
        };

        const pause = () => {
            video.pause();
            syncState();
        };

        if (playTrigger) {
            playTrigger.addEventListener('click', play);
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                if (video.paused || video.ended) {
                    play();
                } else {
                    pause();
                }
            });
        }

        const updateProgress = () => {
            if (!video.duration) return;
            const pct = (video.currentTime / video.duration) * 100;
            if (progressFill) progressFill.style.width = `${pct}%`;
            if (timeEl) timeEl.textContent = formatTime(video.duration - video.currentTime);
        };

        video.addEventListener('timeupdate', updateProgress);
        video.addEventListener('loadedmetadata', updateProgress);
        video.addEventListener('play', syncState);
        video.addEventListener('pause', syncState);
        video.addEventListener('ended', () => {
            if (playTrigger) playTrigger.classList.remove('hidden');
            syncState();
        });

        if (progress) {
            progress.addEventListener('click', (e) => {
                const rect = progress.getBoundingClientRect();
                const ratio = (e.clientX - rect.left) / rect.width;
                if (video.duration) {
                    video.currentTime = ratio * video.duration;
                }
            });
        }
    });

    const closeVideoModal = (modal) => {
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        modal.querySelectorAll('video').forEach((video) => video.pause());
    };

    document.addEventListener('click', (e) => {
        const openBtn = e.target.closest('[data-video-modal-open]');
        if (openBtn) {
            const modal = document.getElementById(openBtn.dataset.videoModalOpen);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
            return;
        }

        const closeBtn = e.target.closest('[data-video-modal-close]');
        if (closeBtn) {
            closeVideoModal(closeBtn.closest('[data-video-modal]'));
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('[data-video-modal]:not(.hidden)').forEach(closeVideoModal);
    });
});