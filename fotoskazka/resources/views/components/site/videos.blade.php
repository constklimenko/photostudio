@props(['videos' => [], 'title' => null])

@php
    $videos = collect($videos);
    $horizontalVideos = $videos->where('type', 'horizontal');
    $verticalVideos = $videos->where('type', 'vertical');
@endphp

@if ($horizontalVideos->isNotEmpty() || $verticalVideos->isNotEmpty())
    <section class="mt-16" data-aos="fade-up">
        @if ($title)
            <h2 class="font-heading text-2xl font-normal tracking-wide text-white text-center mb-12">{{ $title }}</h2>
        @endif

        @if ($horizontalVideos->isNotEmpty())
            <div class="max-w-5xl mx-auto space-y-12">
                @foreach ($horizontalVideos as $video)
                    <div>
                        <h3 class="font-heading text-xl font-normal tracking-wide text-white mb-4">
                            {{ $video->pivot->caption ?: $video->title }}
                        </h3>
                        <div class="relative aspect-video rounded-xl overflow-hidden bg-black shadow-lg shadow-black/30">
                            <x-site.video-player :video="$video" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($verticalVideos->isNotEmpty())
            @if ($horizontalVideos->isNotEmpty())
                <h2 class="font-heading text-2xl font-normal tracking-wide text-white text-center mt-16 mb-12">Вертикальные видео</h2>
            @endif
            <div class="video-slider" data-video-slider>
                @foreach ($verticalVideos as $video)
                    <div>
                        <h3 class="font-heading text-base font-normal tracking-wide text-white mb-3 text-center truncate">
                            {{ $video->pivot->caption ?: $video->title }}
                        </h3>
                        <div class="relative {{ $video->isRotated() ? 'aspect-video' : 'aspect-[9/16]' }} rounded-xl overflow-hidden bg-black shadow-lg shadow-black/30">
                            <x-site.video-player :video="$video" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endif