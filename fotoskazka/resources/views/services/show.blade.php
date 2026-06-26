@extends('layouts.site')

@section('title', $service->seo_title ?: ($service->title . ' — Фотосказка'))
@section('meta_description', $service->seo_description ?: $service->short_description)

@section('content')

<section class="py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-sm text-gray-500 mb-8">
            <a href="{{ route('home') }}" class="hover:text-amber-600 transition">Главная</a>
            <span class="mx-2">/</span>
            <a href="{{ route('services.index') }}" class="hover:text-amber-600 transition">Услуги</a>
            @if ($service->category)
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ $service->category->name }}</span>
            @endif
        </nav>

        @if ($service->cover)
            <div class="aspect-[16/9] bg-gray-100 rounded-xl overflow-hidden mb-10">
                <img src="{{ Storage::url($service->cover->thumbnail_path ?? $service->cover->file_path) }}"
                     alt="{{ $service->title }}"
                     class="w-full h-full object-cover">
            </div>
        @endif

        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $service->title }}</h1>

        @if ($service->price_from)
            <p class="mt-4 text-2xl font-bold text-amber-600">от {{ number_format($service->price_from, 0, ',', ' ') }} ₽</p>
        @endif

        @if ($service->short_description)
            <p class="mt-4 text-lg text-gray-600 leading-relaxed">{{ $service->short_description }}</p>
        @endif

        @if ($service->description)
            <div class="mt-8 prose prose-gray max-w-none">
                {!! $service->description !!}
            </div>
        @endif
    </div>
</section>

<section id="inquiry-form" class="py-20 bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center">Записаться на {{ \Illuminate\Support\Str::lower($service->title) }}</h2>
        <p class="mt-3 text-gray-500 text-center">Заполните форму, и мы свяжемся с вами</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('inquiry.store') }}" class="mt-8 space-y-5">
            @csrf
            <input type="hidden" name="service_id" value="{{ $service->id }}">

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

            <button type="submit"
                    class="w-full px-6 py-3 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition">
                Отправить заявку
            </button>
        </form>
    </div>
</section>

@if ($serviceList->isNotEmpty())
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center">Другие услуги</h2>

            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($serviceList as $other)
                    <a href="{{ route('services.show', $other->slug) }}" class="group block bg-white rounded-xl border border-gray-100 p-5 hover:shadow-lg transition">
                        <h3 class="font-semibold text-gray-900 group-hover:text-amber-600 transition">{{ $other->title }}</h3>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
