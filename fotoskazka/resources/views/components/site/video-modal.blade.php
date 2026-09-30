@props(['id', 'videos', 'title' => 'Видео'])

@php
    $videos = collect($videos);
@endphp

@if ($videos->isNotEmpty())
    <div id="{{ $id }}" class="fixed inset-0 z-[1000] hidden" role="dialog" aria-modal="true" aria-label="{{ $title }}" data-video-modal>
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" data-video-modal-close></div>

        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="relative w-full max-w-4xl bg-[#0a0a0a] border border-[#1a1a1a] rounded-3xl shadow-2xl shadow-black/50 overflow-hidden">
                <div class="flex items-center justify-between px-6 sm:px-8 pt-6 pb-2">
                    <h2 class="font-heading text-xl sm:text-2xl font-normal tracking-wide text-white">{{ $title }}</h2>
                    <button type="button" data-video-modal-close
                            class="p-2 text-gray-400 hover:text-white transition rounded-lg hover:bg-[#1a1a1a]"
                            aria-label="Закрыть">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-4 sm:px-8 pb-8 pt-2 space-y-8 max-h-[80vh] overflow-y-auto">
                    @foreach ($videos as $video)
                        <div>
                            @if ($videos->count() > 1 && $video->title)
                                <h3 class="font-heading text-base font-normal tracking-wide text-white mb-3">{{ $video->title }}</h3>
                            @endif
                            <div class="relative {{ $video->type === 'vertical' && ! $video->isRotated() ? 'aspect-[9/16] max-w-sm mx-auto' : 'aspect-video' }} rounded-xl overflow-hidden bg-black">
                                <x-site.video-player :video="$video" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
