@extends('layouts.site')

@section('title', $service->seo_title ?: ($service->title . ' — Фотосказка'))
@section('meta_description', $service->seo_description ?: $service->short_description)

@section('content')

<section class="py-24" data-aos="fade-up">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-sm text-gray-500 mb-8">
            <a href="{{ route('home') }}" class="hover:text-[#d4af37] transition">Главная</a>
            <span class="mx-2 text-gray-600">/</span>
            <a href="{{ route('services.index') }}" class="hover:text-[#d4af37] transition">Услуги</a>
            @if ($service->category)
                <span class="mx-2 text-gray-600">/</span>
                <span class="text-gray-300">{{ $service->category->name }}</span>
            @endif
        </nav>

        @if ($service->cover)
            <div class="aspect-[16/9] bg-gray-100 rounded-xl overflow-hidden mb-10 shadow-lg shadow-black/30">
                <img src="{{ Storage::url($service->cover->file_path) }}"
                     alt="{{ $service->title }}"
                     class="w-full h-full object-cover">
            </div>
        @endif

        <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide text-white">{{ $service->title }}</h1>

        <div class="mt-6">
            <x-site.share-button :title="$service->title" />
        </div>

        @if ($service->price_from)
            <p class="mt-4 text-2xl font-bold text-[#d4af37]">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</p>
            @if ($service->price_note)
                <p class="mt-1 text-xs text-gray-500">{{ $service->price_note }}</p>
            @endif
        @endif

        @if ($service->short_description)
            <p class="mt-4 text-lg text-gray-400 leading-relaxed">{{ $service->short_description }}</p>
        @endif

        @if ($service->items->isNotEmpty())
            <div class="mt-8 p-6 bg-[#1a1a1a] rounded-xl shadow-lg shadow-black/30">
                <h3 class="font-heading text-lg font-semibold tracking-wide text-white mb-4">Что входит</h3>
                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                    @foreach ($service->items as $item)
                        @php $included = $item->pivot->is_included ?? true; @endphp
                        <li class="flex items-center gap-2 text-sm {{ $included ? 'text-gray-300' : 'text-gray-500' }}">
                            @if ($included)
                                <svg class="w-4 h-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                            {{ $item->label }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($service->albums->isNotEmpty())
            <section class="mt-16" data-aos="fade-up">
                <h2 class="font-heading text-2xl font-normal tracking-wide text-white mb-8">Примеры работ</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($service->albums as $album)
                        <a href="{{ route('portfolio.show', $album->slug) }}"
                           class="group block rounded-xl overflow-hidden bg-[#1a1a1a] shadow-lg shadow-black/30 hover:bg-[#242424] transition">
                            @if ($album->cover)
                                <img src="{{ Storage::url($album->cover->thumbnail_path ?? $album->cover->file_path) }}"
                                     alt="{{ $album->title }}"
                                     class="w-full aspect-[4/3] object-cover group-hover:scale-105 transition duration-500"
                                     loading="lazy">
                            @elseif ($album->videos->isNotEmpty())
                                @php $firstVideo = $album->videos->first(); @endphp
                                <div class="w-full aspect-[4/3] relative bg-[#0a0a0a]">
                                    @if ($firstVideo->thumbnail_url)
                                        <img src="{{ $firstVideo->thumbnail_url }}"
                                             alt="{{ $album->title }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                             loading="lazy">
                                    @endif
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="w-14 h-14 rounded-full bg-black/50 backdrop-blur flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="w-full aspect-[4/3] flex items-center justify-center text-gray-400">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="p-3">
                                <h3 class="font-heading text-sm font-medium tracking-wide text-white group-hover:text-[#d4af37] transition truncate">{{ $album->title }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($service->description)
            <div class="mt-8 prose prose-invert max-w-none">
                {!! $service->description !!}
            </div>
        @endif
    </div>
</section>

<section id="inquiry-form" class="py-24 bg-[#111111]" data-aos="fade-up">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">Записаться на {{ \Illuminate\Support\Str::lower($service->title) }}</h2>
        <p class="mt-3 text-gray-400 text-center">Заполните форму, и мы свяжемся с вами</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-900/30 border border-green-800 text-green-400 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-site.inquiry-form :hidden-service-id="$service->id" />
    </div>
</section>

@if ($serviceList->isNotEmpty())
    <section class="py-24" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-2xl font-normal tracking-wide text-white text-center">Другие услуги</h2>

            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($serviceList as $other)
                    <a href="{{ route('services.show', $other->slug) }}"
                       class="group block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        <div class="aspect-[16/9] bg-gray-100">
                            @if ($other->cover)
                                <img src="{{ Storage::url($other->cover->thumbnail_path) }}"
                                     alt="{{ $other->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            @endif
                        </div>
                        <div class="p-5">
                            <h3 class="font-heading font-semibold tracking-wide text-white group-hover:text-[#d4af37] transition">{{ $other->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
