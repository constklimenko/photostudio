@props([
    'testimonials',
    'title' => 'Отзывы',
    'subtitle' => 'Что говорят наши клиенты',
    'content' => null,
])

@if ($testimonials->isNotEmpty())
    <section class="py-24" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>
            @if ($content)
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $content }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($testimonials as $testimonial)
                    <div class="bg-[#1a1a1a] rounded-xl p-6 shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                         data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        <div class="flex items-center gap-4 mb-4">
                            @if ($testimonial->photo)
                                <img src="{{ $testimonial->photo->getThumbnailUrl() }}"
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
                                <p class="font-medium text-white">{{ $testimonial->client_name }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-400 leading-relaxed">{{ $testimonial->content }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
