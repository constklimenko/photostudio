@extends('layouts.site')

@section('title', 'Страница не найдена — ' . config('app.name'))
@section('meta_description', 'Запрошенная страница не найдена. Вернитесь на главную или выберите нужный раздел сайта.')

@section('content')

<section class="min-h-[60vh] flex items-center py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-aos="fade-up">
        <p class="font-heading text-6xl sm:text-7xl leading-none text-[#d4af37]">404</p>

        <h1 class="mt-6 font-heading text-3xl sm:text-4xl font-normal tracking-wide text-white">
            Страница не найдена
        </h1>

        <p class="mt-4 text-gray-300">
            Возможно, страница удалена или адрес набран с ошибкой.
        </p>

        <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
            <a href="{{ route('home') }}"
               class="inline-block px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg shadow-xl hover:opacity-90 transition">
                На главную
            </a>
            <a href="{{ route('services.index') }}"
               class="inline-block px-8 py-3 border border-[#d4af37] text-[#d4af37] font-semibold uppercase tracking-wider text-sm rounded-lg hover:bg-[#d4af37] hover:text-black transition">
                Услуги
            </a>
            <a href="{{ route('portfolio.index') }}"
               class="inline-block px-8 py-3 border border-[#d4af37] text-[#d4af37] font-semibold uppercase tracking-wider text-sm rounded-lg hover:bg-[#d4af37] hover:text-black transition">
                Портфолио
            </a>
        </div>

        @php
            $notFoundPhone = \App\Services\ContactSettings::phone();
            $notFoundPhoneHref = \App\Services\ContactSettings::phoneHref();
        @endphp

        @if ($notFoundPhone && $notFoundPhoneHref)
            <p class="mt-10 text-sm text-gray-300">
                Не нашли нужное? Позвоните:
                <a href="{{ $notFoundPhoneHref }}" class="text-[#d4af37] hover:opacity-80 transition">{{ $notFoundPhone }}</a>
            </p>
        @endif
    </div>
</section>

@endsection
