@extends('layouts.site')

@section('title', 'Услуги — Фотосказка')
@section('meta_description', 'Профессиональная фотосъёмка для выпускных альбомов, детских садов, школ, семейных и индивидуальных фотосессий, мероприятий и свадеб.')

@section('content')

<section class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold text-gray-900">Наши услуги</h1>
        <p class="mt-3 text-gray-500 max-w-2xl mx-auto">
            Профессиональная фотосъёмка для любых событий. Выберите подходящий формат.
        </p>
    </div>
</section>

@foreach ($categories as $category)
    @if ($category->services->isNotEmpty())
        <section class="py-16 {{ $loop->even ? 'bg-gray-50' : '' }}">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-12">{{ $category->name }}</h2>

                @foreach ($category->services as $service)
                    <article class="flex flex-col lg:flex-row gap-8 mb-16 last:mb-0">
                        @if ($service->cover)
                            <div class="lg:w-5/12 shrink-0">
                                <img src="{{ Storage::url($service->cover->thumbnail_path ?? $service->cover->file_path) }}"
                                     alt="{{ $service->title }}"
                                     class="w-full h-80 object-cover rounded-xl"
                                     loading="lazy">
                            </div>
                        @endif

                        <div class="flex-1 flex flex-col justify-center">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $service->title }}</h3>
                            @if ($service->short_description)
                                <p class="mt-3 text-gray-600 leading-relaxed">{{ $service->short_description }}</p>
                            @endif

                            @if ($service->items->isNotEmpty())
                                <ul class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2">
                                    @foreach ($service->items as $item)
                                        <li class="flex items-center gap-2 text-sm {{ $item->is_included ? 'text-gray-700' : 'text-gray-400' }}">
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
                                    <span class="text-2xl font-bold text-amber-600">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</span>
                                @endif
                                <a href="{{ route('services.show', $service->slug) }}"
                                   class="inline-flex items-center px-6 py-2.5 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition text-sm">
                                    Подробнее
                                </a>
                            </div>
                            @if ($service->price_note)
                                <p class="mt-2 text-xs text-gray-400">{{ $service->price_note }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endforeach

@if ($servicesWithoutCategory->isNotEmpty())
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @foreach ($servicesWithoutCategory as $service)
                <article class="flex flex-col lg:flex-row gap-8 mb-16 last:mb-0">
                    @if ($service->cover)
                        <div class="lg:w-5/12 shrink-0">
                            <img src="{{ Storage::url($service->cover->thumbnail_path ?? $service->cover->file_path) }}"
                                 alt="{{ $service->title }}"
                                 class="w-full h-80 object-cover rounded-xl"
                                 loading="lazy">
                        </div>
                    @endif

                    <div class="flex-1 flex flex-col justify-center">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $service->title }}</h3>
                        @if ($service->short_description)
                            <p class="mt-3 text-gray-600 leading-relaxed">{{ $service->short_description }}</p>
                        @endif

                        @if ($service->items->isNotEmpty())
                            <ul class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2">
                                @foreach ($service->items as $item)
                                    <li class="flex items-center gap-2 text-sm {{ $item->is_included ? 'text-gray-700' : 'text-gray-400' }}">
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
                                <span class="text-2xl font-bold text-amber-600">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</span>
                            @endif
                            <a href="{{ route('services.show', $service->slug) }}"
                               class="inline-flex items-center px-6 py-2.5 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition text-sm">
                                Подробнее
                            </a>
                        </div>
                        @if ($service->price_note)
                            <p class="mt-2 text-xs text-gray-400">{{ $service->price_note }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

<section id="inquiry-form" class="py-20 bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center">Не нашли подходящую услугу?</h2>
        <p class="mt-3 text-gray-500 text-center">Оставьте заявку, и мы подберём формат специально для вас</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('inquiry.store') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
                <input type="text" name="name" id="name" required
                       class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                <input type="tel" name="phone" id="phone" required
                       class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Комментарий</label>
                <textarea name="message" id="message" rows="4"
                          class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
            </div>

            <div class="flex items-start gap-2">
                <input type="checkbox" name="agreed_to_terms" id="agreed_to_terms" required
                       class="mt-1 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                <label for="agreed_to_terms" class="text-sm text-gray-500">
                    Согласен на обработку персональных данных
                    @error('agreed_to_terms') <span class="text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <button type="submit"
                    class="w-full px-6 py-3 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition">
                Отправить заявку
            </button>
        </form>
    </div>
</section>

@endsection
