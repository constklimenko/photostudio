@extends('layouts.site')

@section('title', $album->title . ' — Портфолио — Фотосказка')
@section('meta_description', $album->seo_description ?: $album->description)

@section('content')

<section class="py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-site.breadcrumbs :items="[
            ['label' => 'Главная', 'url' => route('home')],
            ['label' => 'Портфолио', 'url' => route('portfolio.index')],
            ['label' => $album->title],
        ]" />

        <div class="max-w-3xl mx-auto text-center mb-12">
            <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide text-white">{{ $album->title }}</h1>
            @if ($album->description)
                <p class="mt-4 text-lg text-gray-400">{{ $album->description }}</p>
            @endif
            <div class="mt-6 flex justify-center">
                <x-site.share-button :title="$album->title" />
            </div>
        </div>

<x-site.album-photos :album="$album" />

        <x-site.videos :videos="$album->videos" />

        @if ($album->photos->isEmpty() && $album->videos->isEmpty())
            <div class="text-center py-20 text-gray-500">
                <p>В альбоме пока нет фотографий.</p>
            </div>
        @endif
    </div>
</section>

@if ($album->services->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-2xl font-normal tracking-wide text-white text-center mb-8">Эта съёмка доступна в&nbsp;услугах</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-4xl mx-auto">
                @foreach ($album->services as $service)
                    <a href="{{ route('services.show', $service->catalogPath()) }}"
                       class="block bg-[#1a1a1a] rounded-xl shadow-lg shadow-black/30 hover:bg-[#242424] transition overflow-hidden"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
@if ($service->cover)
                             <div class="aspect-[4/3] bg-gray-100">
                                 <img src="{{ $service->cover->getThumbnailUrl() }}"
                                      alt="{{ $service->title }}"
                                      class="w-full h-full object-cover"
                                      loading="lazy">
                             </div>
                        @endif
                        <div class="p-4 text-center">
                            <h3 class="font-heading font-semibold tracking-wide text-white hover:text-[#d4af37] transition">{{ $service->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section id="inquiry-form" class="py-24 {{ $album->services->isNotEmpty() ? '' : 'bg-[#111111]' }}" data-aos="fade-up">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">Записаться на съёмку</h2>
        <p class="mt-3 text-gray-400 text-center">Заполните форму, и мы свяжемся с вами</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-900/30 border border-green-800 text-green-400 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-site.inquiry-form
            :services="$services"
            :selected-service-id="$album->services->first()?->id"
        />
    </div>
</section>

@endsection
