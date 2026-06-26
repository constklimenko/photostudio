@extends('layouts.site')

@section('title', 'Фотосказка — профессиональная фотосъёмка')
@section('meta_description', 'Профессиональная фотосъёмка для ваших важных событий. Услуги фотографа, портфолио, выпускные альбомы.')

@section('content')

<section class="relative bg-gray-50 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
        <div class="max-w-2xl">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
                Сохраняем ваши самые важные моменты
            </h1>
            <p class="mt-6 text-lg text-gray-600 leading-relaxed">
                Профессиональная фотосъёмка для выпускных альбомов, семейных праздников и индивидуальных фотосессий.
            </p>
            <div class="mt-8 flex gap-4">
                <a href="#inquiry-form" class="inline-block px-6 py-3 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition">
                    Оставить заявку
                </a>
                <a href="{{ route('portfolio.index') }}" class="inline-block px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Смотреть портфолио
                </a>
            </div>
        </div>
    </div>
</section>

@if ($services->isNotEmpty())
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center">Наши услуги</h2>
            <p class="mt-3 text-gray-500 text-center">Выберите подходящий формат съёмки</p>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($services as $service)
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

@if ($featuredWorks->isNotEmpty())
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center">Избранные работы</h2>
            <p class="mt-3 text-gray-500 text-center">Наши лучшие проекты</p>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($featuredWorks as $album)
                    <a href="{{ route('portfolio.show', $album->slug) }}" class="group block relative overflow-hidden rounded-xl aspect-[4/3] bg-gray-100">
                        @if ($album->cover)
                            <img src="{{ Storage::url($album->cover->thumbnail_path ?? $album->cover->file_path) }}"
                                 alt="{{ $album->title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-4">
                            <h3 class="text-white font-semibold">{{ $album->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($testimonials->isNotEmpty())
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center">Отзывы</h2>
            <p class="mt-3 text-gray-500 text-center">Что говорят наши клиенты</p>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($testimonials as $testimonial)
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <div class="flex items-center gap-4 mb-4">
                            @if ($testimonial->photo)
                                <img src="{{ Storage::url($testimonial->photo->thumbnail_path ?? $testimonial->photo->file_path) }}"
                                     alt="{{ $testimonial->client_name }}"
                                     class="w-12 h-12 rounded-full object-cover bg-gray-100">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <p class="font-medium text-gray-900">{{ $testimonial->client_name }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $testimonial->content }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($latestPosts->isNotEmpty())
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center">Последние статьи</h2>
            <p class="mt-3 text-gray-500 text-center">Полезная информация из мира фотографии</p>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($latestPosts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="group block bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition">
                        @if ($post->cover)
                            <div class="aspect-[16/9] bg-gray-100">
                                <img src="{{ Storage::url($post->cover->thumbnail_path ?? $post->cover->file_path) }}"
                                     alt="{{ $post->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </div>
                        @else
                            <div class="aspect-[16/9] bg-gray-100"></div>
                        @endif
                        <div class="p-5">
                            <p class="text-xs text-gray-400 mb-2">{{ $post->published_at->format('d.m.Y') }}</p>
                            <h3 class="font-semibold text-gray-900 group-hover:text-amber-600 transition line-clamp-2">{{ $post->title }}</h3>
                            @if ($post->excerpt)
                                <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $post->excerpt }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section id="inquiry-form" class="py-20">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center">Оставить заявку</h2>
        <p class="mt-3 text-gray-500 text-center">Заполните форму, и мы свяжемся с вами</p>

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

            @if ($serviceList->isNotEmpty())
                <div>
                    <label for="service_id" class="block text-sm font-medium text-gray-700 mb-1">Услуга</label>
                    <select name="service_id" id="service_id"
                            class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="">— Выберите услугу —</option>
                        @foreach ($serviceList as $service)
                            <option value="{{ $service->id }}">{{ $service->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Комментарий</label>
                <textarea name="message" id="message" rows="4"
                          class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
            </div>

            <button type="submit"
                    class="w-full px-6 py-3 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition">
                Отправить
            </button>
        </form>
    </div>
</section>

@endsection
