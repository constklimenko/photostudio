@extends('layouts.site')

@section('title', $page?->seo_title ?: 'Портфолио — Фотосказка')
@section('meta_description', $page?->seo_description ?: 'Фотопортфолио профессионального фотографа. Свадебные, семейные, индивидуальные фотосессии и выпускные альбомы.')

@section('content')

<section class="relative bg-[#111111] text-white py-24" data-aos="fade-up">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="font-heading text-4xl sm:text-5xl font-normal tracking-wide">{{ $page?->title ?: 'Портфолио' }}</h1>
        <p class="mt-4 text-lg text-gray-400 max-w-2xl mx-auto">
            {{ $page?->subtitle ?: 'Избранные проекты, которые рассказывают истории' }}
        </p>
    </div>
</section>

<section class="py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if ($albums->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($albums as $i => $album)
                    <a href="{{ route('portfolio.show', $album->slug) }}"
                       class="group block relative overflow-hidden rounded-xl bg-black shadow-lg shadow-black/30 {{ $i % 4 === 0 ? 'sm:col-span-2 sm:row-span-2' : ($i % 5 === 0 ? 'sm:row-span-2' : '') }}"
                       data-aos="fade-up" data-aos-delay="{{ ($i % 9) * 100 }}">
@if ($album->cover)
                             <img src="{{ $album->cover->getDisplayUrl() ?? $album->cover->getUrl() }}"
                                  alt="{{ $album->title }}"
                                  class="w-full h-full object-cover group-hover:scale-105 transition duration-700"
                                  loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                            <div class="absolute bottom-0 left-0 right-0 p-5">
                                <h3 class="font-heading text-white font-semibold tracking-wide text-lg">{{ $album->title }}</h3>
                                @if ($album->description)
                                    <p class="text-gray-300 text-sm mt-1 line-clamp-2">{{ $album->description }}</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-20">
                <p class="text-gray-500 text-lg">Портфолио пока пусто.</p>
            </div>
        @endif
    </div>
</section>
@endsection
