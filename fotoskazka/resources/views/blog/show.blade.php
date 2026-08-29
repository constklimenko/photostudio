@extends('layouts.site')

@section('title', $post->seo_title ?: ($post->title . ' — Блог — Фотосказка'))
@section('meta_description', $post->seo_description ?: $post->excerpt)

@section('content')

<section class="py-24" data-aos="fade-up">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-12">

            <article class="flex-1 min-w-0">
                <x-site.breadcrumbs :items="array_merge([
                    ['label' => 'Главная', 'url' => route('home')],
                    ['label' => 'Блог', 'url' => route('blog.index')],
                ], $post->category ? [['label' => $post->category->name]] : [], [
                    ['label' => $post->title],
                ])" />

                @if ($post->cover)
                    <div class="aspect-[16/9] bg-gray-100 rounded-xl overflow-hidden mb-8 shadow-lg shadow-black/30">
                        <img src="{{ $post->cover->getUrl() }}"
                             alt="{{ $post->title }}"
                             class="w-full h-full object-cover">
                    </div>
                @endif

                <p class="text-sm text-gray-500 mb-3">{{ $post->published_at->format('d.m.Y') }}</p>
                <h1 class="font-heading text-3xl sm:text-4xl font-normal tracking-wide text-white">{{ $post->title }}</h1>

                @if ($post->content)
                    <div class="mt-8 prose prose-invert max-w-none">
                        {!! $post->content !!}
                    </div>
                @endif

                @if ($post->albums->isNotEmpty())
                    <section class="mt-16" data-aos="fade-up">
                        <h2 class="font-heading text-2xl font-normal tracking-wide text-white mb-6">Фотоальбомы</h2>
                        <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-thin">
                            @foreach ($post->albums as $album)
                                <div class="snap-start shrink-0 w-72">
                                    <a href="{{ route('portfolio.show', $album->slug) }}"
                                       class="block bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/30 hover:bg-[#242424] transition">
@if ($album->cover)
                                             <div class="aspect-[4/3] bg-gray-100">
                                                 <img src="{{ $album->cover->getThumbnailUrl() }}"
                                                      alt="{{ $album->title }}"
                                                      class="w-full h-full object-cover"
                                                      loading="lazy">
                                             </div>
@elseif ($album->photos->isNotEmpty())
                                             @php $firstPhoto = $album->photos->sortBy('sort_order')->first(); @endphp
                                             <div class="aspect-[4/3] bg-gray-100">
                                                 <img src="{{ $firstPhoto->media->getThumbnailUrl() }}"
                                                      alt="{{ $album->title }}"
                                                      class="w-full h-full object-cover"
                                                      loading="lazy">
                                             </div>
                                        @elseif ($album->videos->isNotEmpty())
                                            @php $firstVideo = $album->videos->first(); @endphp
                                            <div class="aspect-[4/3] bg-gray-100 relative">
                                                @if ($firstVideo->thumbnail_url)
                                                    <img src="{{ $firstVideo->thumbnail_url }}"
                                                         alt="{{ $album->title }}"
                                                         class="w-full h-full object-cover"
                                                         loading="lazy">
                                                @endif
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <div class="w-14 h-14 rounded-full bg-black/50 backdrop-blur flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="aspect-[4/3] bg-gray-100"></div>
                                        @endif
                                        <div class="p-4">
                                            <h3 class="font-heading font-semibold tracking-wide text-white text-sm">{{ $album->title }}</h3>
                                            @if ($album->description)
                                                <p class="mt-1 text-xs text-gray-400 line-clamp-2">{{ $album->description }}</p>
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <x-site.videos :videos="$post->videos" title="Видео" />

                <section id="inquiry-form" class="mt-16 pt-12 border-t border-[#2a2a2a]">
                    <h2 class="font-heading text-2xl font-normal tracking-wide text-white text-center">Записаться на съёмку</h2>
                    <p class="mt-2 text-gray-400 text-center">Заполните форму, и мы свяжемся с вами</p>

                    @if (session('success'))
                        <div class="mt-6 p-4 bg-green-900/30 border border-green-800 text-green-400 rounded-lg text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    <x-site.inquiry-form :services="$services" class="mt-8 max-w-2xl mx-auto space-y-5" />
                </section>
            </article>

            <aside class="lg:w-80 shrink-0">
                <div class="space-y-8">
                    <form action="{{ route('blog.index') }}" method="GET">
                        <label class="block text-sm font-semibold text-white mb-2">Поиск</label>
                        <div class="flex">
                            <input type="search" name="q" value="{{ request('q') }}"
                                   placeholder="Поиск..."
                                   class="flex-1 rounded-l-lg bg-transparent border border-[#2a2a2a] px-4 py-2 text-sm text-white placeholder-gray-500 focus:border-[#d4af37] focus:ring-[#d4af37]">
                            <button type="submit"
                                    class="px-6 py-2 bg-gold text-black font-semibold uppercase tracking-wider text-xs rounded-r-lg hover:opacity-90 transition">
                                Найти
                            </button>
                        </div>
                    </form>

                    @if ($categories->isNotEmpty())
                        <div>
                            <h3 class="font-heading text-sm font-semibold tracking-wide text-white mb-3">Категории</h3>
                            <ul class="space-y-1">
                                @foreach ($categories as $category)
                                    <li>
                                        <a href="{{ route('blog.index', ['category' => $category->slug]) }}"
                                           class="flex justify-between text-sm text-gray-400 hover:text-[#d4af37] transition">
                                            <span>{{ $category->name }}</span>
                                            <span class="text-gray-500">{{ $category->posts_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($recentPosts->isNotEmpty())
                        <div>
                            <h3 class="font-heading text-sm font-semibold tracking-wide text-white mb-3">Последние записи</h3>
                            <ul class="space-y-3">
                                @foreach ($recentPosts as $recent)
                                    <li class="flex gap-3">
@if ($recent->cover)
                                             <img src="{{ $recent->cover->getThumbnailUrl() }}"
                                                  alt=""
                                                  class="w-14 h-14 rounded-lg object-cover shrink-0"
                                                  loading="lazy">
                                        @else
                                            <div class="w-14 h-14 rounded-lg bg-[#1a1a1a] shrink-0"></div>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('blog.show', $recent->slug) }}"
                                               class="text-sm font-medium text-gray-300 hover:text-[#d4af37] transition line-clamp-2">{{ $recent->title }}</a>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $recent->published_at?->format('d.m.Y') }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </aside>

        </div>
    </div>
</section>

@endsection
