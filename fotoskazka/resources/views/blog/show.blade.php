@extends('layouts.site')

@section('title', $post->seo_title ?: ($post->title . ' — Блог — Фотосказка'))
@section('meta_description', $post->seo_description ?: $post->excerpt)

@section('content')

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-12">

            <article class="flex-1 min-w-0">
                <nav class="text-sm text-gray-500 mb-8">
                    <a href="{{ route('home') }}" class="hover:text-amber-600 transition">Главная</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('blog.index') }}" class="hover:text-amber-600 transition">Блог</a>
                    @if ($post->category)
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">{{ $post->category->name }}</span>
                    @endif
                </nav>

                @if ($post->cover)
                    <div class="aspect-[16/9] bg-gray-100 rounded-xl overflow-hidden mb-8">
                        <img src="{{ Storage::url($post->cover->thumbnail_path ?? $post->cover->file_path) }}"
                             alt="{{ $post->title }}"
                             class="w-full h-full object-cover">
                    </div>
                @endif

                <p class="text-sm text-gray-400 mb-3">{{ $post->published_at->format('d.m.Y') }}</p>
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $post->title }}</h1>

                @if ($post->content)
                    <div class="mt-8 prose prose-gray max-w-none">
                        {!! $post->content !!}
                    </div>
                @endif

                @if ($post->albums->isNotEmpty())
                    <section class="mt-16">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Фотоальбомы</h2>
                        <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-thin">
                            @foreach ($post->albums as $album)
                                <div class="snap-start shrink-0 w-72">
                                    <a href="{{ route('portfolio.show', $album->slug) }}"
                                       class="block bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition">
                                        @if ($album->cover)
                                            <div class="aspect-[4/3] bg-gray-100">
                                                <img src="{{ Storage::url($album->cover->thumbnail_path ?? $album->cover->file_path) }}"
                                                     alt="{{ $album->title }}"
                                                     class="w-full h-full object-cover"
                                                     loading="lazy">
                                            </div>
                                        @elseif ($album->photos->isNotEmpty())
                                            @php $firstPhoto = $album->photos->sortBy('sort_order')->first(); @endphp
                                            <div class="aspect-[4/3] bg-gray-100">
                                                <img src="{{ Storage::url($firstPhoto->media->thumbnail_path ?? $firstPhoto->media->file_path) }}"
                                                     alt="{{ $album->title }}"
                                                     class="w-full h-full object-cover"
                                                     loading="lazy">
                                            </div>
                                        @else
                                            <div class="aspect-[4/3] bg-gray-100"></div>
                                        @endif
                                        <div class="p-4">
                                            <h3 class="font-semibold text-gray-900 text-sm">{{ $album->title }}</h3>
                                            @if ($album->description)
                                                <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ $album->description }}</p>
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section id="inquiry-form" class="mt-16 pt-12 border-t border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900 text-center">Записаться на съёмку</h2>
                    <p class="mt-2 text-gray-500 text-center">Заполните форму, и мы свяжемся с вами</p>

                    @if (session('success'))
                        <div class="mt-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('inquiry.store') }}" class="mt-8 max-w-2xl mx-auto space-y-5">
                        @csrf

                        <div>
                            <label for="service_id" class="block text-sm font-medium text-gray-700 mb-1">Услуга</label>
                            <select name="service_id" id="service_id"
                                    class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="">— Без услуги —</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->title }}</option>
                                @endforeach
                            </select>
                        </div>

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

                        <div class="flex items-start gap-2">
                            <input type="checkbox" name="agreed_to_terms" id="agreed_to_terms" required
                                   class="mt-1 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <label for="agreed_to_terms" class="text-sm text-gray-500">
                                Согласен на обработку персональных данных
                                @error('agreed_to_terms') <span class="text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <button type="submit"
                                class="w-full px-6 py-3 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition">
                            Отправить заявку
                        </button>
                    </form>
                </section>
            </article>

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
                                           class="flex justify-between text-sm text-gray-600 hover:text-amber-600 transition">
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
                </div>
            </aside>

        </div>
    </div>
</section>

@endsection
