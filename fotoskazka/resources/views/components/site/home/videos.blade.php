@props([
    'videos',
    'title' => 'Видеогалерея',
    'subtitle' => 'Смотрите наши работы в движении',
    'content' => null,
])

@if ($videos->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>
            @if ($content)
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $content }}</p>
            @endif

            <x-site.videos :videos="$videos" />
        </div>
    </section>
@endif
