@props(['album', 'display'])

@php
    $isGrid = $display === \App\Enums\ShootingAlbumDisplay::Grid;
@endphp

<section class="mt-16" data-aos="fade-up">
    <h2 class="font-heading text-2xl font-normal tracking-wide text-white mb-8">Фото со съёмок</h2>

    @if ($isGrid)
        <x-site.album-photos :album="$album" />
    @else
        <a href="{{ route('portfolio.show', $album->slug) }}"
           class="group flex items-stretch max-w-md rounded-xl overflow-hidden bg-[#1a1a1a] shadow-lg shadow-black/30 hover:bg-[#242424] transition">
            <div class="w-32 sm:w-40 shrink-0 bg-[#0a0a0a] overflow-hidden">
                @if ($album->cover)
                    <img src="{{ $album->cover->getThumbnailUrl() }}"
                         alt="{{ $album->title }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                         loading="lazy">
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
            </div>
            <div class="flex-1 p-5">
                <h3 class="font-heading text-base font-medium tracking-wide text-white group-hover:text-[#d4af37] transition">{{ $album->title }}</h3>
                @if ($album->description)
                    <p class="mt-1.5 text-sm text-gray-400 line-clamp-2">{{ $album->description }}</p>
                @endif
            </div>
        </a>
    @endif
</section>