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
                    <a href="{{ route('cabinet.album', $album) }}" class="bg-[#111111] rounded-xl overflow-hidden border border-[#1a1a1a] hover:border-[#d4af37]/40 transition group">
                        @if($album->cover)
                            <img src="{{ $album->cover->getThumbnailUrl() }}"
                                 alt="{{ $album->cover->alt_text }}"
                                 class="w-full h-48 object-cover group-hover:scale-105 transition duration-500"
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
                                <span class="text-sm text-amber-400 group-hover:text-amber-300 transition-colors">Открыть →</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-16">
            <h2 class="font-heading text-xl font-normal tracking-wide text-white">Комментарии</h2>

            <div class="mt-6 space-y-4 max-w-3xl">
                @forelse($project->comments as $comment)
                    <div class="bg-[#111111] rounded-xl border border-[#1a1a1a] p-5">
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span class="font-medium text-gray-300">{{ $comment->user->name }}</span>
                            <span>{{ $comment->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <p class="mt-3 text-gray-300">{!! nl2br(e($comment->body)) !!}</p>
                    </div>
                @empty
                    <p class="text-gray-500">Комментариев пока нет</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('cabinet.project.comments.store', $project) }}" class="mt-8 max-w-3xl" novalidate>
                @csrf

                <label for="comment-body" class="block text-sm font-medium text-gray-400">Ваш комментарий</label>
                <textarea id="comment-body" name="body" rows="4" required
                          class="mt-2 w-full rounded-lg bg-[#111111] border border-[#1a1a1a] text-white p-3 focus:border-[#d4af37]/50 focus:outline-none">{{ old('body') }}</textarea>

                @error('body')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror

                <button type="submit"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#d4af37] px-6 py-3 text-sm font-semibold text-black hover:bg-[#e3c25e] transition">
                    Оставить комментарий
                </button>
            </form>
        </div>

    </div>
</section>
@endsection
