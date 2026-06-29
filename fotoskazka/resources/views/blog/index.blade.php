@extends('layouts.site')

@section('title', 'Блог — Фотосказка')
@section('meta_description', 'Полезные статьи о фотосъёмке, подготовке к выпускному и семейных фотосессиях.')

@section('content')

<section class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold text-gray-900">Блог</h1>
        <p class="mt-3 text-gray-500 max-w-2xl mx-auto">
            Полезные статьи, советы и новости из мира фотографии
        </p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-12">

            <div class="flex-1 min-w-0">
                @if ($posts->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                        @foreach ($posts as $post)
                            <article class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition">
                                @if ($post->cover)
                                    <a href="{{ route('blog.show', $post->slug) }}" class="block aspect-[16/9] bg-gray-100 overflow-hidden">
                                        <img src="{{ Storage::url($post->cover->thumbnail_path ?? $post->cover->file_path) }}"
                                             alt="{{ $post->title }}"
                                             class="w-full h-full object-cover hover:scale-105 transition duration-500"
                                             loading="lazy">
                                    </a>
                                @else
                                    <div class="aspect-[16/9] bg-gray-100"></div>
                                @endif
                                <div class="p-5">
                                    <p class="text-xs text-gray-400 mb-2">{{ $post->published_at->format('d.m.Y') }}</p>
                                    <h2 class="font-semibold text-gray-900">
                                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-amber-600 transition">{{ $post->title }}</a>
                                    </h2>
                                    @if ($post->excerpt)
                                        <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $post->excerpt }}</p>
                                    @endif
                                    <a href="{{ route('blog.show', $post->slug) }}"
                                       class="mt-3 inline-flex items-center text-sm font-medium text-amber-600 hover:text-amber-700 transition">
                                        Читать далее
                                        <svg class="w-3.5 h-3.5 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="text-center py-20">
                        <p class="text-gray-500 text-lg">Статей пока нет.</p>
                    </div>
                @endif
            </div>

            <aside class="lg:w-80 shrink-0">
                <div class="space-y-8">
                    <form action="{{ route('blog.index') }}" method="GET">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Поиск</label>
                        <div class="flex">
                            <input type="search" name="q" value="{{ request('q') }}"
                                   placeholder="Поиск..."
                                   class="flex-1 rounded-l-lg border-gray-300 px-4 py-2 text-sm focus:border-amber-500 focus:ring-amber-500">
                            <button type="submit"
                                    class="px-4 py-2 bg-amber-600 text-white rounded-r-lg hover:bg-amber-700 transition text-sm">
                                Найти
                            </button>
                        </div>
                    </form>

                    @if ($categories->isNotEmpty())
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">Категории</h3>
                            <ul class="space-y-1">
                                @foreach ($categories as $category)
                                    <li>
                                        <a href="{{ route('blog.index', ['category' => $category->slug]) }}"
                                           class="flex justify-between text-sm {{ request('category') === $category->slug ? 'text-amber-600 font-medium' : 'text-gray-600 hover:text-amber-600' }} transition">
                                            <span>{{ $category->name }}</span>
                                            <span class="text-gray-400">{{ $category->posts_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($recentPosts->isNotEmpty())
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">Последние записи</h3>
                            <ul class="space-y-3">
                                @foreach ($recentPosts as $recent)
                                    <li class="flex gap-3">
                                        @if ($recent->cover)
                                            <img src="{{ Storage::url($recent->cover->thumbnail_path ?? $recent->cover->file_path) }}"
                                                 alt=""
                                                 class="w-14 h-14 rounded-lg object-cover shrink-0"
                                                 loading="lazy">
                                        @else
                                            <div class="w-14 h-14 rounded-lg bg-gray-100 shrink-0"></div>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('blog.show', $recent->slug) }}"
                                               class="text-sm font-medium text-gray-900 hover:text-amber-600 transition line-clamp-2">{{ $recent->title }}</a>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $recent->published_at?->format('d.m.Y') }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (request('q') || request('category'))
                        <div>
                            <a href="{{ route('blog.index') }}" class="text-sm text-amber-600 hover:text-amber-700 transition">
                                &larr; Сбросить фильтры
                            </a>
                        </div>
                    @endif
                </div>
            </aside>

        </div>
    </div>
</section>

@endsection
