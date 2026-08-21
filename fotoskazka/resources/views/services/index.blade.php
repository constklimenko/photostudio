@extends('layouts.site')

@section('title', $page?->seo_title ?: 'Услуги — Фотосказка')
@section('meta_description', $page?->seo_description ?: 'Профессиональная фотосъёмка для выпускных альбомов, детских садов, школ, семейных и индивидуальных фотосессий, мероприятий и свадеб.')

@section('content')

<section class="bg-[#111111] py-24" data-aos="fade-up">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="font-heading text-4xl font-normal tracking-wide text-white">{{ $page?->title ?: 'Наши услуги' }}</h1>
        <p class="mt-3 text-gray-400 max-w-2xl mx-auto">
            {{ $page?->subtitle ?: 'Профессиональная фотосъёмка для любых событий. Выберите подходящий формат.' }}
        </p>
    </div>
</section>

@foreach ($categories as $category)
    @if ($category->services->isNotEmpty())
        <section class="py-24 {{ $loop->even ? 'bg-[#111111]' : '' }}" data-aos="fade-up">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-heading text-3xl font-normal tracking-wide text-white mb-12">{{ $category->name }}</h2>

                @foreach ($category->services as $service)
                    <article class="flex flex-col lg:flex-row gap-8 mb-16 last:mb-0" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
@if ($service->cover)
                             <div class="lg:w-5/12 shrink-0">
                                 <img src="{{ $service->cover->getThumbnailUrl() }}"
                                      alt="{{ $service->title }}"
                                      class="w-full h-80 object-cover rounded-xl shadow-lg shadow-black/30"
                                      loading="lazy">
                             </div>
                        @endif

                        <div class="flex-1 flex flex-col justify-center">
                            <h3 class="font-heading text-2xl font-normal tracking-wide text-white">{{ $service->title }}</h3>
                            @if ($service->short_description)
                                <p class="mt-3 text-gray-400 leading-relaxed">{{ $service->short_description }}</p>
                            @endif

                            @if ($service->items->isNotEmpty())
                                <ul class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2">
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
                            @endif

                            <div class="mt-6 flex items-center gap-6">
                                @if ($service->price_from)
                                    <span class="text-2xl font-bold text-[#d4af37]">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</span>
                                @endif
                                <a href="{{ route('services.show', $service->slug) }}"
                                   class="inline-flex items-center px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg hover:opacity-90 transition">
                                    Подробнее
                                </a>
                            </div>
                            @if ($service->price_note)
                                <p class="mt-2 text-xs text-gray-500">{{ $service->price_note }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endforeach

@if ($servicesWithoutCategory->isNotEmpty())
    <section class="py-24" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @foreach ($servicesWithoutCategory as $service)
                <article class="flex flex-col lg:flex-row gap-8 mb-16 last:mb-0" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
@if ($service->cover)
                         <div class="lg:w-5/12 shrink-0">
                             <img src="{{ $service->cover->getThumbnailUrl() }}"
                                  alt="{{ $service->title }}"
                                  class="w-full h-80 object-cover rounded-xl shadow-lg shadow-black/30"
                                  loading="lazy">
                         </div>
                    @endif

                    <div class="flex-1 flex flex-col justify-center">
                        <h3 class="font-heading text-2xl font-normal tracking-wide text-white">{{ $service->title }}</h3>
                        @if ($service->short_description)
                            <p class="mt-3 text-gray-400 leading-relaxed">{{ $service->short_description }}</p>
                        @endif

                        @if ($service->items->isNotEmpty())
                            <ul class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2">
                                @foreach ($service->items as $item)
                                    <li class="flex items-center gap-2 text-sm {{ $item->is_included ? 'text-gray-300' : 'text-gray-500' }}">
                                        @if ($item->is_included)
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
                        @endif

                        <div class="mt-6 flex items-center gap-6">
                            @if ($service->price_from)
                                <span class="text-2xl font-bold text-[#d4af37]">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</span>
                            @endif
                            <a href="{{ route('services.show', $service->slug) }}"
                               class="inline-flex items-center px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg hover:opacity-90 transition">
                                Подробнее
                            </a>
                        </div>
                        @if ($service->price_note)
                            <p class="mt-2 text-xs text-gray-500">{{ $service->price_note }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

<section id="inquiry-form" class="py-24 bg-[#111111]" data-aos="fade-up">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">Не нашли подходящую услугу?</h2>
        <p class="mt-3 text-gray-400 text-center">Оставьте заявку, и мы подберём формат специально для вас</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-900/30 border border-green-800 text-green-400 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-site.inquiry-form />
    </div>
</section>

@endsection
