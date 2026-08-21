# Редизайн страницы /blog

## 1. Анализ референсов

Изучены два файла:

- `Blog Right Sidebar – Smearing.html` — страница списка постов
- `Blog detail – Smearing.html` — страница отдельного поста

### Структура страницы списка (`/blog`)

```
┌──────────────────────────────────────────────────┬──────────────┐
│  Лента постов (col-lg-9)                        │  Сайдбар     │
│                                                  │  (col-lg-3)  │
│  ┌──────────────────────┐ ┌──────────────────┐   │              │
│  │  [обложка]           │ │  [обложка]       │   │  Поиск       │
│  │  Заголовок           │ │  Заголовок       │   │  Категории   │
│  │  Дата / Автор        │ │  Дата / Автор    │   │              │
│  │  excerpt             │ │  excerpt         │   │  Последние   │
│  │  [Читать далее]      │ │  [Читать далее]  │   │  посты       │
│  └──────────────────────┘ └──────────────────┘   │              │
│                                                  │  Облако      │
│  < 1 2 3 ... >                                  │  тегов       │
│                                                  │              │
└──────────────────────────────────────────────────┴──────────────┘
```

### Структура детальной страницы (`/blog/{slug}`)

```
┌──────────────────────────────────────────────────┬──────────────┐
│  [обложка на всю ширину]                        │  Сайдбар     │
│  Заголовок                                      │  (такой же)  │
│  Дата публикации                                 │              │
│                                                  │              │
│  Контент (prose-стили)                           │              │
│                                                  │              │
│  ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─             │              │
│  [Форма заявки]                                  │              │
│  ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─             │              │
│                                                  │              │
└──────────────────────────────────────────────────┴──────────────┘
```

### Элементы сайдбара

| Элемент | Описание |
|---|---|
| Поиск | `input[type=search]` → GET /blog?q=... |
| Категории | Список `category` → фильтрация постов |
| Последние посты | 3–5 последних постов с превью |
| Облако тегов | (опционально, если будет поле tags) |

## 2. Техническая реализация

### 2.1. Маршруты

```php
Route::controller(BlogController::class)->prefix('blog')->name('blog.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{slug}', 'show')->name('show');
});
```

### 2.2. Контроллер

```php
class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($request->q, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            }))
            ->when($request->category, fn ($q, $slug) => $q->whereHas('category', fn ($q) => $q->where('slug', $slug)))
            ->orderBy('published_at', 'desc')
            ->paginate(6);

        $categories = Category::query()->where('type', 'post')->get();
        $recentPosts = Post::query()->where('is_published', true)->latest('published_at')->limit(5)->get();

        return view('blog.index', compact('posts', 'categories', 'recentPosts'));
    }

    public function show(string $slug)
    {
        $post = Post::query()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->with('cover')
            ->firstOrFail();

        $categories = Category::query()->where('type', 'post')->get();
        $recentPosts = Post::query()->where('is_published', true)->whereKeyNot($post->id)->latest('published_at')->limit(5)->get();

        $services = Service::query()->where('is_published', true)->orderBy('sort_order')->get(['id', 'title']);

        return view('blog.show', compact('post', 'categories', 'recentPosts', 'services'));
    }
}
```

### 2.3. Blade-шаблоны

#### `blog/index.blade.php`

- Hero-секция (заголовок «Блог», подзаголовок)
- Двухколоночный layout: основная часть (9 колонок) + сайдбар (3 колонки)
- Посты выводятся в сетке 2×N карточек:
  - cover (или placeholder)
  - дата (формат `d.m.Y`)
  - заголовок (ссылка)
  - excerpt (2 строки)
  - ссылка «Читать далее»
- Пагинация (Laravel `->links()`)
- Сайдбар:
  - Форма поиска
  - Список категорий (со счётчиком постов)
  - Последние посты (cover + заголовок + дата)
- Пустое состояние

#### `blog/show.blade.php`

- Обложка (16:9)
- Заголовок
- Дата
- Контент (`prose`)
- Форма заявки (аналогично portfolio/show)
- Сайдбар (как на списке)

### 2.4. Форма заявки

Форма заявки внизу поста (как на portfolio/show):
- Если пост привязан к услугам — первая услуга предвыбрана
- Селект всех услуг (с опцией «— Без услуги —»)
- Поля: имя, телефон, комментарий, согласие на ПД
- POST → `route('inquiry.store')`

## 3. План реализации

| Шаг | Действие | Файлы |
|---|---|---|
| 1 | BlogController | `app/Http/Controllers/BlogController.php` |
| 2 | blog/index.blade.php | `resources/views/blog/index.blade.php` |
| 3 | blog/show.blade.php | `resources/views/blog/show.blade.php` |
| 4 | Routes | `routes/web.php` |
| 5 | Проверка тестов | `php artisan test` |
| 6 | Обновить changelog.md | — |

## 4. Что НЕ делается на этом этапе

- Комментарии (явно исключены)
- Теги для постов (опционально, потом)
- RSS-лента
- Кастомная пагинация (используется стандартная Laravel)
- Боковая панель на главной (уже есть блок «Последние статьи»)
