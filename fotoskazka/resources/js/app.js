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

        if (heroImage.complete && heroImage.naturalWidth > 0) {
            swapToOriginal();
        } else {
            heroImage.addEventListener('load', swapToOriginal);
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
});