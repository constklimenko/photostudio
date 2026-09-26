@props([
    'title' => 'ФОТОСКАЗКА УФА',
    'subtitle' => null,
    'buttons' => [],
    'images' => null,
])

<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-black" id="hero-block">
    @if ($images?->isNotEmpty())
        @php
            $heroBg = $images->first();
            $heroCacheUrl = $heroBg->getDisplayUrl();
            $heroOriginalUrl = $heroBg->getUrl();
        @endphp
        <div class="absolute inset-0">
            <img src="{{ $heroCacheUrl ?: $heroOriginalUrl }}"
                 @if ($heroCacheUrl && $heroOriginalUrl) data-original="{{ $heroOriginalUrl }}" @endif
                 alt=""
                 fetchpriority="high"
                 class="w-full h-full object-cover">
        </div>
    @endif

    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

    <div class="relative z-10 text-center px-4 max-w-4xl mx-auto">
        <h3 class="font-heading tracking-wider text-white text-5xl sm:text-6xl md:text-7xl font-normal mb-6 leading-snug">
            {{ $title }}
        </h3>
        @if (filled($subtitle))
            <p class="text-lg sm:text-xl text-gray-300 max-w-2xl mx-auto mb-10 leading-relaxed">
                {{ $subtitle }}
            </p>
        @endif

        @if (filled($buttons))
            <div class="flex flex-wrap items-center justify-center gap-4">
                @foreach ($buttons as $button)
                    <a href="{{ $button['url'] }}"
                       data-hero-button="{{ $button['type'] }}"
                       class="inline-block px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg shadow-xl hover:opacity-90 transition">
                        {{ $button['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
