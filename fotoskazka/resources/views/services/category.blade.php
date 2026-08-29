@extends('layouts.site')

@section('title', $category->seo_title ?: ($category->name . ' — Фотосказка'))
@section('meta_description', $category->seo_description ?: $category->description)

@section('content')

<section class="py-24" data-aos="fade-up">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-site.breadcrumbs :items="array_merge([
            ['label' => 'Главная', 'url' => route('home')],
            ['label' => 'Услуги', 'url' => route('services.index')],
        ], array_map(fn ($ancestor) => [
            'label' => $ancestor->name,
            'url' => route('services.show', $ancestor->catalogPath()),
        ], $category->ancestors()), [
            ['label' => $category->name],
        ])" />

        @if ($category->cover)
            <div class="aspect-[16/9] bg-gray-100 rounded-xl overflow-hidden mb-10 shadow-lg shadow-black/30">
                <img src="{{ $category->cover->getUrl() }}"
                     alt="{{ $category->name }}"
                     class="w-full h-full object-cover">
            </div>
        @endif

        <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide text-white">{{ $category->name }}</h1>

        @if ($category->description)
            <div class="mt-6 prose prose-invert max-w-none">
                {!! $category->description !!}
            </div>
        @endif

        @if ($category->price_from)
            <p class="mt-6 text-2xl font-bold text-[#d4af37]">от {{ number_format($category->price_from, 0, ',', ' ') }} ₽</p>
            @if ($category->price_note)
                <p class="mt-1 text-xs text-gray-500">{{ $category->price_note }}</p>
            @endif
        @endif
    </div>
</section>

@if ($category->children->isNotEmpty())
    <section class="py-24" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-2xl font-normal tracking-wide text-white mb-8">Разделы</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($category->children as $child)
                    <a href="{{ route('services.show', $child->catalogPath()) }}"
                       class="group block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        <div class="aspect-[16/9] bg-gray-100">
                            @if ($child->cover)
                                <img src="{{ $child->cover->getThumbnailUrl() }}"
                                     alt="{{ $child->name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            @endif
                        </div>
                        <div class="p-5">
                            <h3 class="font-heading font-semibold tracking-wide text-white group-hover:text-[#d4af37] transition">{{ $child->name }}</h3>
                            @if ($child->price_from)
                                <p class="mt-2 text-sm font-bold text-[#d4af37]">от {{ number_format($child->price_from, 0, ',', ' ') }} ₽</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($category->services->isNotEmpty())
    <section class="py-24 {{ $category->children->isNotEmpty() ? 'bg-[#111111]' : '' }}" data-aos="fade-up">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-2xl font-normal tracking-wide text-white mb-8">Варианты оформления</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($category->services as $service)
                    <a href="{{ route('services.show', $service->catalogPath()) }}"
                       class="group block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        <div class="aspect-[16/9] bg-gray-100">
                            @if ($service->cover)
                                <img src="{{ $service->cover->getThumbnailUrl() }}"
                                     alt="{{ $service->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            @endif
                        </div>
                        <div class="p-5">
                            <h3 class="font-heading font-semibold tracking-wide text-white group-hover:text-[#d4af37] transition">{{ $service->title }}</h3>
                            @if ($service->short_description)
                                <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $service->short_description }}</p>
                            @endif
                            <div class="mt-3 flex items-center justify-between">
                                @if ($service->price_from)
                                    <span class="text-sm font-bold text-[#d4af37]">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</span>
                                @endif
                                <span class="text-sm text-[#d4af37] font-semibold uppercase tracking-wider group-hover:opacity-70 transition">Подробнее</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section id="inquiry-form" class="py-24 bg-[#111111]" data-aos="fade-up">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">Оставить заявку</h2>
        <p class="mt-3 text-gray-400 text-center">Заполните форму, и мы свяжемся с вами</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-900/30 border border-green-800 text-green-400 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-site.inquiry-form />
    </div>
</section>

@endsection
