@props([
    'albums',
    'title' => 'Фото со съёмок',
    'subtitle' => 'Загляните на съёмочную площадку',
    'content' => null,
])

@if ($albums->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>
            @if ($content)
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $content }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($albums as $album)
                    <a href="{{ route('portfolio.show', $album->slug) }}"
                       class="group block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        @if ($album->cover)
                            <div class="aspect-[4/3] bg-gray-100">
                                <img src="{{ $album->cover->getThumbnailUrl() }}"
                                     alt="{{ $album->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                     loading="lazy">
                            </div>
                        @else
                            <div class="aspect-[4/3] bg-gray-100 flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <h3 class="font-heading font-semibold tracking-wide text-white group-hover:text-[#d4af37] transition">{{ $album->title }}</h3>
                            @if ($album->description)
                                <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $album->description }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
