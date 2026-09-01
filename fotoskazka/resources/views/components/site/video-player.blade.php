@props(['video'])

@php
    $rotation = (int) $video->rotation;
    $rotateTransform = match ($rotation) {
        90 => 'rotate(90deg)',
        -90 => 'rotate(-90deg)',
        default => null,
    };
@endphp

@if ($video->is_upload)
    @if ($rotateTransform)
        <div class="relative w-full h-full" data-video-player>
            <video
                class="rotated-media absolute"
                playsinline
                preload="none"
                controlsList="nodownload noremoteplayback"
                disablepictureinpicture
                oncontextmenu="return false"
                style="position:absolute;top:50%;left:50%;width:56.25%;height:177.78%;max-width:none;max-height:none;object-fit:cover;transform:translate(-50%,-50%) {{ $rotateTransform }};"
            >
                <source src="{{ $video->source_url }}" type="video/mp4">
            </video>

            <button type="button"
                    class="absolute inset-0 z-10 flex items-center justify-center group"
                    data-video-play-trigger
                    aria-label="Воспроизвести">
                <span class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 backdrop-blur flex items-center justify-center transition group-hover:bg-white/30"
                      data-video-play-icon>
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white ml-1" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M8 5v14l11-7z"></path>
                    </svg>
                </span>
            </button>

            <div class="absolute bottom-0 inset-x-0 z-10 px-3 py-2 hidden" data-video-controls>
                <div class="flex items-center gap-3">
                    <button type="button"
                            class="text-white hover:text-gray-300 transition flex items-center justify-center"
                            data-video-toggle
                            aria-label="Пауза или воспроизведение">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M6 4h4v16H6zM14 4h4v16h-4z" data-video-toggle-pause-icon></path>
                            <path d="M8 5v14l11-7z" data-video-toggle-play-icon class="hidden"></path>
                        </svg>
                    </button>
                    <div class="relative flex-1 h-1.5 bg-white/30 rounded-full overflow-hidden cursor-pointer"
                         data-video-progress>
                        <div class="absolute inset-y-0 left-0 bg-white rounded-full" style="width:0%"
                             data-video-progress-fill></div>
                    </div>
                    <span class="text-white text-xs tabular-nums" data-video-time>0:00</span>
                </div>
            </div>
        </div>
    @else
        <video
            class="w-full h-full"
            controls
            playsinline
            preload="none"
            controlsList="nodownload noremoteplayback"
            disablepictureinpicture
            oncontextmenu="return false"
        >
            <source src="{{ $video->source_url }}" type="video/mp4">
        </video>
    @endif
@else
    <iframe src="{{ $video->embed_url }}"
            title="{{ $video->title }}"
            class="w-full h-full"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
            loading="lazy">
    </iframe>
@endif
