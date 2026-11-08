@props([
    'text',
    'title' => 'О студии',
])

@if (filled($text))
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-white">{{ $title ?: 'О студии' }}</h2>
        <div class="mt-4 text-gray-400 leading-relaxed">
            {!! $text !!}
        </div>
    </section>
@endif
