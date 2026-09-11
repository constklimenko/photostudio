@extends('layouts.site')

@section('title', $album->title . ' — Фотосказка')

@section('content')
<section class="bg-[#111111] text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <a href="{{ $album->project ? route('cabinet.project', $album->project) : route('cabinet.index') }}"
               class="text-gray-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide">{{ $album->title }}</h1>
                @if($album->project)
                    <p class="mt-1 text-sm text-gray-400">{{ $album->project->title }}</p>
                @endif
            </div>
        </div>

        @if($album->description)
            <p class="mt-6 text-gray-400 max-w-3xl">{{ $album->description }}</p>
        @endif

        <span class="mt-6 inline-block text-sm text-gray-500">
            {{ $photos->total() }} {{ Str::plural('фото', $photos->total()) }}
        </span>
    </div>
</section>

<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($photos->isEmpty())
            <div class="bg-[#111111] rounded-xl p-10 text-center border border-[#1a1a1a]">
                <p class="text-gray-400">В альбоме пока нет фотографий</p>
            </div>
        @else
            <x-site.album-photos :album="$album" :photos="$photos" />

            @if($photos->hasPages())
                <div class="mt-10">
                    {{ $photos->links() }}
                </div>
            @endif
        @endif

    </div>
</section>
@endsection