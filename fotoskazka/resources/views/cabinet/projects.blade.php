@extends('layouts.site')

@section('title', 'Мои проекты — Фотосказка')

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
            <a href="{{ route('cabinet.index') }}" class="text-gray-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide">
                @if($user->hasRole('class_manager'))
                    Ваш проект
                @else
                    Мои проекты
                @endif
            </h1>
        </div>
        <p class="mt-4 text-lg text-gray-400">Здравствуйте, {{ $user->name }}</p>
    </div>
</section>

<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($projects->isEmpty())
            <div class="bg-[#111111] rounded-xl p-10 text-center border border-[#1a1a1a]">
                <p class="text-gray-400">Нет доступных проектов</p>
            </div>
        @else
            <p class="mb-6 text-sm text-gray-500">{{ $projects->count() }} {{ Str::plural('проект', $projects->count()) }}</p>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($projects as $project)
                    <a href="#"
                       class="block bg-[#111111] rounded-xl p-6 border border-[#1a1a1a] hover:border-[#2a2a2a] transition-colors">
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
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</section>
@endsection
