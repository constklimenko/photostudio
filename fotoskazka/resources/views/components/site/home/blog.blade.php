@props([
    'posts',
    'title' => 'Последние статьи',
    'subtitle' => 'Полезная информация из мира фотографии',
    'content' => null,
])

@if ($posts->isNotEmpty())
    <section class="py-24 bg-[#111111]" data-aos="fade-up">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
            <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>
            @if ($content)
                <p class="mt-4 text-sm text-gray-400 text-center max-w-2xl mx-auto">{{ $content }}</p>
            @endif

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}"
                       class="group block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                        @if ($post->cover)
                            <div class="aspect-[16/9] bg-gray-100">
                                <img src="{{ $post->cover->getThumbnailUrl() }}"
                                     alt="{{ $post->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </div>
                        @else
                            <div class="aspect-[16/9] bg-gray-100"></div>
                        @endif
                        <div class="p-5">
                            <p class="text-xs text-gray-400 mb-2">{{ $post->published_at->format('d.m.Y') }}</p>
                            <h3 class="font-heading font-semibold tracking-wide text-white group-hover:text-[#d4af37] transition line-clamp-2">{{ $post->title }}</h3>
                            @if ($post->excerpt)
                                <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $post->excerpt }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
