<section class="ar-teaser relative overflow-hidden py-24" data-aos="fade-up" data-aos-delay="50">
    <div class="ar-teaser__bg absolute inset-0"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gold/10 border border-gold/30 text-[#d4af37] text-xs font-semibold uppercase tracking-widest">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M11.933 1.5a.5.5 0 0 1 .469.325l1.434 3.588a.5.5 0 0 0 .28.28l3.589 1.434a.5.5 0 0 1 0 .933l-3.589 1.434a.5.5 0 0 0-.28.28l-1.434 3.588a.5.5 0 0 1-.933 0l-1.434-3.588a.5.5 0 0 0-.28-.28l-3.589-1.434a.5.5 0 0 1 0-.933l3.589-1.434a.5.5 0 0 0 .28-.28l1.434-3.588A.5.5 0 0 1 11.933 1.5Z"/>
            </svg>
            Скоро
        </span>

        <h2 class="mt-6 font-heading text-3xl sm:text-4xl lg:text-5xl font-normal leading-snug text-white max-w-4xl mx-auto">
            Скоро в&nbsp;Фотосказке&nbsp;—<br>
            <span class="text-[#d4af37]">оживающие фотографии</span>
        </h2>

        <p class="mt-5 max-w-2xl mx-auto text-base sm:text-lg text-gray-400 leading-relaxed">
            Обычные снимки превратятся в&nbsp;живые воспоминания: фото оживёт, сохранив атмосферу мгновения.
        </p>

        <p class="mt-3 text-sm text-gray-500">
            Следите за&nbsp;новостями&nbsp;— подробности скоро появятся на&nbsp;сайте.
        </p>

        <div class="relative mt-12 max-w-4xl mx-auto aspect-[4/3] sm:aspect-[16/10] lg:aspect-video rounded-2xl overflow-hidden bg-[#111111] ring-1 ring-white/10 shadow-2xl shadow-black/40">
            <img
                src="{{ asset('images/ar-teaser.jpg') }}"
                alt="Фотография, которая скоро оживёт в дополненной реальности Фотосказки"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover"
            >

            <div class="absolute inset-0 pointer-events-none bg-gradient-to-t from-black/50 via-transparent to-black/20"></div>
            <div class="ar-teaser__glow absolute -right-14 -top-14 w-56 h-56 rounded-full pointer-events-none" aria-hidden="true"></div>

            <div class="ar-teaser__lines absolute inset-0 pointer-events-none opacity-20" aria-hidden="true">
                <span class="ar-teaser__line ar-teaser__line--1 absolute left-[12%] top-0 h-full w-px bg-[#d4af37]"></span>
                <span class="ar-teaser__line ar-teaser__line--2 absolute left-[50%] top-0 h-full w-px bg-[#d4af37]"></span>
                <span class="ar-teaser__line ar-teaser__line--3 absolute left-[82%] top-0 h-full w-px bg-[#d4af37]"></span>
            </div>
        </div>
    </div>

    <style>
        .ar-teaser__bg {
            background-image:
                radial-gradient(ellipse 60% 70% at 85% 30%, rgba(212, 175, 55, 0.10), transparent 65%),
                linear-gradient(to bottom, #0a0a0a 0%, #0d0c0a 50%, #111111 100%);
        }

        .ar-teaser__glow {
            background: radial-gradient(circle, rgba(212, 175, 55, 0.16), transparent 70%);
        }

        .ar-teaser__lines .ar-teaser__line {
            animation: arTeaserScan 4.5s ease-in-out infinite;
            animation-delay: calc(var(--line-index, 0) * 0.55s);
            opacity: 0;
        }
        .ar-teaser__line--1 { --line-index: 0; }
        .ar-teaser__line--2 { --line-index: 1; }
        .ar-teaser__line--3 { --line-index: 2; }

        @keyframes arTeaserScan {
            0%, 70%, 100% { opacity: 0; transform: scaleY(0.9); }
            30%, 45% { opacity: 1; transform: scaleY(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .ar-teaser__line {
                animation: none !important;
                opacity: 0.25 !important;
            }
        }
    </style>
</section>