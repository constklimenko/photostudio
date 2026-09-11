@extends('layouts.site')

@section('title', $project->title . ' — Фотосказка')

@php
    $statusBadgeClasses = [
        'draft' => 'text-gray-400',
        'shooting_completed' => 'text-sky-400',
        'reshoot' => 'text-amber-400',
        'processing' => 'text-blue-400',
        'layout_approval' => 'text-emerald-400',
        'printing' => 'text-red-400',
        'completed' => 'text-emerald-400',
        'archived' => 'text-gray-500',
    ];
@endphp

@section('content')
<section class="bg-[#111111] text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('cabinet.projects') }}" class="text-gray-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide">{{ $project->title }}</h1>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-4">
            <span class="inline-block px-3 py-1 text-xs font-medium rounded-full {{ $statusBadgeClasses[$project->status->value] ?? 'text-gray-400' }}">
                {{ $project->status->label() }}
            </span>

            @if($project->shooting_date)
                <span class="text-sm text-gray-400">
                    <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ $project->shooting_date->format('d.m.Y') }}
                </span>
            @endif

            <span class="text-sm text-gray-500">
                {{ $albums->count() }} {{ Str::plural('альбом', $albums->count()) }}
            </span>
        </div>

        @if($project->description)
            <p class="mt-6 text-gray-400 max-w-3xl">{{ $project->description }}</p>
        @endif
    </div>
</section>

<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($albums->isEmpty())
            <div class="bg-[#111111] rounded-xl p-10 text-center border border-[#1a1a1a]">
                <p class="text-gray-400">Нет доступных альбомов</p>
            </div>
        @else
            <h2 class="font-heading text-xl font-normal tracking-wide text-white">Альбомы</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($albums as $album)
                    <div class="bg-[#111111] rounded-xl overflow-hidden border border-[#1a1a1a]">
                        @if($album->cover)
                            <img src="{{ $album->cover->getThumbnailUrl() }}"
                                 alt="{{ $album->cover->alt_text }}"
                                 class="w-full h-48 object-cover"
                                 loading="lazy">
                        @else
                            <div class="w-full h-48 bg-[#1a1a1a] flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <h3 class="font-heading text-lg font-medium text-white">{{ $album->title }}</h3>
                            @if($album->description)
                                <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $album->description }}</p>
                            @endif
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-sm text-gray-500">
                                    {{ $album->photos_count }} {{ Str::plural('фото', $album->photos_count) }}
                                </span>
                                <span class="text-sm text-amber-400">Открыть →</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</section>
@endsection
