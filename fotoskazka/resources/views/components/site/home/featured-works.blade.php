@props([
    'albums',
    'title' => 'Избранные работы',
    'subtitle' => 'Наши лучшие проекты',
    'content' => null,
])

@if ($albums?->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up" data-home-block="featured-works">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>
            @if ($content)
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $content }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($albums as $album)
                    <a href="{{ route('portfolio.show', $album->slug) }}"
                       class="group block relative overflow-hidden rounded-xl aspect-[4/3] bg-black shadow-lg shadow-black/30"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        @if ($album->cover)
                            <img src="{{ $album->cover->getThumbnailUrl() }}"
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
