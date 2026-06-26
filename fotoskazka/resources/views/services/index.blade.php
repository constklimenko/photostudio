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
                <h2 class="text-2xl font-bold text-gray-900">{{ $category->name }}</h2>

                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($category->services as $service)
                        <a href="{{ route('services.show', $service->slug) }}" class="group block bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition">
                            @if ($service->cover)
                                <div class="aspect-[4/3] bg-gray-100">
                                    <img src="{{ Storage::url($service->cover->thumbnail_path ?? $service->cover->file_path) }}"
                                         alt="{{ $service->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                </div>
                            @else
                                <div class="aspect-[4/3] bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="p-5">
                                <h3 class="font-semibold text-gray-900 group-hover:text-amber-600 transition">{{ $service->title }}</h3>
                                @if ($service->short_description)
                                    <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $service->short_description }}</p>
                                @endif
                                @if ($service->price_from)
                                    <p class="mt-3 font-medium text-amber-600">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endforeach

@if ($servicesWithoutCategory->isNotEmpty())
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($servicesWithoutCategory as $service)
                    <a href="{{ route('services.show', $service->slug) }}" class="group block bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition">
                        @if ($service->cover)
                            <div class="aspect-[4/3] bg-gray-100">
                                <img src="{{ Storage::url($service->cover->thumbnail_path ?? $service->cover->file_path) }}"
                                     alt="{{ $service->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </div>
                        @else
                            <div class="aspect-[4/3] bg-gray-100 flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <h3 class="font-semibold text-gray-900 group-hover:text-amber-600 transition">{{ $service->title }}</h3>
                            @if ($service->short_description)
                                <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $service->short_description }}</p>
                            @endif
                            @if ($service->price_from)
                                <p class="mt-3 font-medium text-amber-600">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
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
