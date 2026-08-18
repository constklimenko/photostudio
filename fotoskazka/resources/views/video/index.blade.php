@extends('layouts.site')

@section('title', $page?->seo_title ?: 'Видеогалерея — Фотосказка')
@section('meta_description', $page?->seo_description ?: 'Смотрите наши работы в формате видео')

@section('content')

<section class="relative py-24 bg-[#111111] overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="font-heading text-4xl sm:text-5xl font-normal tracking-wide text-white">
            {{ $page?->title ?: 'Видеогалерея' }}
        </h1>
        @if ($page?->subtitle)
            <p class="mt-4 text-lg text-gray-400 max-w-2xl mx-auto">{{ $page->subtitle }}</p>
        @endif
    </div>
</section>

@if ($videos->isEmpty())
    <section class="py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400">Видео пока не добавлены</p>
        </div>
    </section>
@else
    @if ($horizontalVideos->isNotEmpty())
        <section class="py-24" data-aos="fade-up">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-5xl mx-auto space-y-12">
                    @foreach ($horizontalVideos as $video)
                        <div data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                            <h3 class="font-heading text-xl font-normal tracking-wide text-white mb-4">{{ $video->title }}</h3>
                            <div class="aspect-video rounded-xl overflow-hidden bg-black shadow-lg shadow-black/30">
                                @if ($video->is_upload)
                                    <video class="w-full h-full" controls playsinline preload="metadata">
                                        <source src="{{ Storage::url($video->file_path) }}" type="video/mp4">
                                    </video>
                                @else
                                    <iframe src="{{ $video->embed_url }}"
                                            title="{{ $video->title }}"
                                            class="w-full h-full"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen
                                            loading="lazy">
                                    </iframe>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($verticalVideos->isNotEmpty())
        <section class="py-24 bg-[#111111]" data-aos="fade-up">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-heading text-2xl font-normal tracking-wide text-white text-center mb-12">Вертикальные видео</h2>
                <div class="video-slider" data-video-slider data-aos="fade-up">
                    @foreach ($verticalVideos as $video)
                        <div data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                            <h3 class="font-heading text-base font-normal tracking-wide text-white mb-3 text-center truncate">{{ $video->title }}</h3>
                            <div class="aspect-[9/16] rounded-xl overflow-hidden bg-black shadow-lg shadow-black/30">
                                @if ($video->is_upload)
                                    <video class="w-full h-full object-cover" controls playsinline preload="metadata">
                                        <source src="{{ Storage::url($video->file_path) }}" type="video/mp4">
                                    </video>
                                @else
                                    <iframe src="{{ $video->embed_url }}"
                                            title="{{ $video->title }}"
                                            class="w-full h-full"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen
                                            loading="lazy">
                                    </iframe>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endif

@endsection
