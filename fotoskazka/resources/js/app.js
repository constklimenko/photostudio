import $ from 'jquery';
import 'slick-carousel';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import AOS from 'aos';
import 'aos/dist/aos.css';

document.addEventListener('DOMContentLoaded', () => {
    const heroImage = document.querySelector('#hero-block img[data-original]');

    if (heroImage) {
        const desktopViewport = window.matchMedia('(min-width: 768px)');

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

        const maybeSwap = () => {
            if (desktopViewport.matches && heroImage.complete && heroImage.naturalWidth > 0) {
                swapToOriginal();
            }
        };

        heroImage.addEventListener('load', () => {
            if (desktopViewport.matches) {
                swapToOriginal();
            }
        });

        desktopViewport.addEventListener('change', maybeSwap);
        maybeSwap();
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
});