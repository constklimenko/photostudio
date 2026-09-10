@extends('layouts.site')

@section('title', 'Личный кабинет — Фотосказка')

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
        <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide">Личный кабинет</h1>
        <p class="mt-4 text-lg text-gray-400">Здравствуйте, {{ $user->name }}</p>
    </div>
</section>

<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($user->hasRole('parent'))
            <h2 class="font-heading text-xl font-normal tracking-wide text-white">Назначенные альбомы</h2>

            @if($albums->isEmpty())
                <div class="mt-8 bg-[#111111] rounded-xl p-10 text-center border border-[#1a1a1a]">
                    <p class="text-gray-400">Нет назначенных альбомов</p>
                    <p class="mt-2 text-sm text-gray-600">Альбомы появятся здесь, когда фотограф назначит их вам.</p>
                </div>
            @else
                <p class="mt-2 text-sm text-gray-500">Доступно {{ $albums->count() }} {{ Str::plural('альбом', $albums->count()) }}</p>
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
                                @if($album->project)
                                    <p class="mt-1 text-sm text-gray-500">{{ $album->project->title }}</p>
                                @endif
                                @if($album->description)
                                    <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $album->description }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        @elseif(isset($projects))
            @if($user->hasRole('class_manager'))
                <h2 class="font-heading text-xl font-normal tracking-wide text-white">Ваш проект</h2>
            @elseif($user->hasAnyRole(['admin', 'photographer']))
                <h2 class="font-heading text-xl font-normal tracking-wide text-white">Проекты</h2>
            @else
                <h2 class="font-heading text-xl font-normal tracking-wide text-white">Ваши проекты</h2>
            @endif

            @if($projects->isEmpty())
                <div class="mt-8 bg-[#111111] rounded-xl p-10 text-center border border-[#1a1a1a]">
                    <p class="text-gray-400">Нет доступных проектов</p>
                </div>
            @else
                <p class="mt-2 text-sm text-gray-500">{{ $projects->count() }} {{ Str::plural('проект', $projects->count()) }}</p>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($projects as $project)
                        <div class="bg-[#111111] rounded-xl p-6 border border-[#1a1a1a]">
                            <h3 class="font-heading text-lg font-medium text-white">{{ $project->title }}</h3>

                            <span class="inline-block mt-2 px-3 py-1 text-xs font-medium rounded-full {{ $statusBadgeClasses[$project->status->value] ?? 'text-gray-400' }}">
                                {{ $project->status->label() }}
                            </span>

                            @if($project->shooting_date)
                                <p class="mt-3 text-sm text-gray-400">
                                    <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $project->shooting_date->format('d.m.Y') }}
                                </p>
                            @endif

                            <div class="mt-4 flex gap-4 text-sm text-gray-500">
                                <span>{{ $project->client_albums_count }} {{ Str::plural('альбом', $project->client_albums_count) }}</span>
                                <span>{{ $project->photos_count }} {{ Str::plural('фото', $project->photos_count) }}</span>
                            </div>

                            @php
                                $visibleAlbums = $user->hasRole('class_manager')
                                    ? $project->albums->where('type', 'client')
                                    : $project->albums;
                            @endphp

                            @if($visibleAlbums->isNotEmpty())
                                <div class="mt-4 pt-4 border-t border-[#1a1a1a] space-y-1">
                                    @foreach($visibleAlbums->take(3) as $album)
                                        <p class="text-sm text-gray-400">{{ $album->title }}</p>
                                    @endforeach
                                    @if($visibleAlbums->count() > 3)
                                        <p class="text-xs text-gray-600">и ещё {{ $visibleAlbums->count() - 3 }}...</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

    </div>
</section>
@endsection
