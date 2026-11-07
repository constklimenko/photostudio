@extends('layouts.site')

@section('title', $page->seo_title ?: ($page->title.' — Фотосказка'))
@section('meta_description', $page->seo_description ?: $page->subtitle ?: 'Политика обработки персональных данных на сайте Фотосказка')

@section('content')

<section class="py-24" data-aos="fade-up">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-site.breadcrumbs :items="[
            ['label' => 'Главная', 'url' => route('home')],
            ['label' => $page->title],
        ]" />

        <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide text-white">{{ $page->title }}</h1>

        @if ($page->subtitle)
            <p class="mt-3 text-gray-400">{{ $page->subtitle }}</p>
        @endif

        @if ($page->content)
            <div class="mt-8 prose prose-invert max-w-none">
                {!! $page->content !!}
            </div>
        @endif

        @php
            $contactEmail = \App\Services\ContactSettings::email();
            $contactPhone = \App\Services\ContactSettings::phone();
            $contactPhoneHref = \App\Services\ContactSettings::phoneHref();
        @endphp
        @if ($contactEmail || ($contactPhone && $contactPhoneHref))
            <div class="mt-12 pt-8 border-t border-[#2a2a2a]">
                <h2 class="font-heading text-xl font-normal tracking-wide text-white mb-4">По вопросам обработки персональных данных</h2>
                <ul class="space-y-2 text-sm text-gray-400">
                    @if ($contactEmail)
                        <li>
                            <a href="mailto:{{ $contactEmail }}" class="underline hover:text-[#d4af37] transition">{{ $contactEmail }}</a>
                        </li>
                    @endif
                    @if ($contactPhone && $contactPhoneHref)
                        <li>
                            <a href="{{ $contactPhoneHref }}" class="underline hover:text-[#d4af37] transition">{{ $contactPhone }}</a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        <p class="mt-8 text-xs text-gray-600">Редакция от {{ $page->updated_at?->format('d.m.Y') }}</p>
    </div>
</section>

@endsection
