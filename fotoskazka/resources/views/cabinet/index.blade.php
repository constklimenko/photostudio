@extends('layouts.site')

@section('title', 'Личный кабинет — Фотосказка')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="font-heading text-3xl font-normal tracking-wide text-gray-900">Личный кабинет</h1>
    <p class="mt-4 text-lg text-gray-600">Здравствуйте, {{ $user->name }}</p>

    @if($user->hasRole('parent') && isset($albums))
        @if($albums->isEmpty())
            <p class="mt-8 text-gray-500">Нет назначенных альбомов.</p>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($albums as $album)
                    <div class="bg-white rounded-lg shadow p-6">
                        @if($album->cover)
                            <img src="{{ $album->cover->getThumbnailUrl() }}" alt="{{ $album->cover->alt_text }}" class="w-full h-48 object-cover rounded mb-4">
                        @endif
                        <h3 class="font-heading text-lg font-medium text-gray-900">{{ $album->title }}</h3>
                        @if($album->project)
                            <p class="mt-1 text-sm text-gray-500">{{ $album->project->title }}</p>
                        @endif
                        @if($album->description)
                            <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $album->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @elseif(isset($projects))
        @if($projects->isEmpty())
            <p class="mt-8 text-gray-500">Нет доступных проектов.</p>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($projects as $project)
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-heading text-lg font-medium text-gray-900">{{ $project->title }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $project->status->label() }}</p>
                        @if($project->shooting_date)
                            <p class="mt-2 text-sm text-gray-600">{{ $project->shooting_date->format('d.m.Y') }}</p>
                        @endif
                        <div class="mt-4 flex gap-4 text-sm text-gray-500">
                            <span>{{ $project->albums_count }} {{ Str::plural('альбом', $project->albums_count) }}</span>
                            <span>{{ $project->photos_count }} {{ Str::plural('фото', $project->photos_count) }}</span>
                        </div>
                        @if($project->albums_count > 0)
                            <div class="mt-4 space-y-1">
                                @foreach($project->albums->take(3) as $album)
                                    <p class="text-sm text-gray-600">{{ $album->title }}</p>
                                @endforeach
                                @if($project->albums_count > 3)
                                    <p class="text-xs text-gray-400">и ещё {{ $project->albums_count - 3 }}...</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection
