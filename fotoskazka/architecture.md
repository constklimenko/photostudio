# Архитектура проекта

## Стек

| Компонент     | Версия        |
|---------------|---------------|
| PHP           | 8.4.24        |
| Laravel       | 13.16.1       |
| MySQL         | 8.x           |
| Filament      | 4.11.7        |
| Livewire      | 3.8.1         |
| Node          | 20+           |
| Vite          | 8.x           |

## Структура приложения

```
app/
├── Actions/                    # Action-классы (бизнес-логика)
│   ├── Album/
│   │   ├── CreateAlbum.php     # Создание альбома с медиа и фото
│   │   └── ImportAlbumFromYandexDisk.php  # Импорт альбома из папки Яндекс.Диска
│   └── Inquiry/
│       └── CreateProjectFromInquiry.php  # Транзакция: заявка → проект
├── Console/
│   └── Commands/
│       ├── MakeFilamentUser.php
│       ├── MediaRegenerateThumbnails.php
│       ├── MediaPruneImageCache.php      # Очистка кэша display/lightbox
│       └── MediaTestStorage.php          # Проверка подключения к диску
├── Filesystem/
│   └── YandexDiskPaginatedAdapter.php    # Листинг Яндекс.Диска с пагинацией (чанки по 100)
├── Jobs/
│   ├── SendInquiryNotifications.php  # Очередь: email + Telegram уведомления
│   ├── ProcessMedia.php              # Очередь: обработка Media (metadata + thumbnail)
│   └── ImportAlbumFromYandexDisk.php # Очередь: импорт альбома из папки Яндекс.Диска
├── Filament/
│   ├── Resources/              # Filament ресурсы (CRUD)
│   │   ├── Albums/
│   │   │   ├── AlbumResource.php
│   │   │   ├── Pages/
│   │   │   │   ├── CreateAlbum.php
│   │   │   │   ├── EditAlbum.php       # Дозагрузка фото
│   │   │   │   ├── ImportFromYandexDisk.php  # Импорт альбома из папки Яндекс.Диска
│   │   │   │   ├── ListAlbums.php
│   │   │   │   └── UploadPhotos.php    # Drag&drop загрузка
│   │   │   ├── RelationManagers/
│   │   │   │   ├── PhotosRelationManager.php
│   │   │   │   └── VideosRelationManager.php
│   │   │   ├── Schemas/
│   │   │   │   └── AlbumForm.php
│   │   │   └── Tables/
│   │   │       └── AlbumsTable.php
│   │   ├── Categories/
│   │   ├── FaqItems/
│   │   ├── Inquiries/
│   │   │   └── Schemas/
│   │   │       └── InquiryForm.php
│   │   ├── Media/
│   │   │   └── Schemas/
│   │   │       └── MediaForm.php
│   │   ├── NotificationSettings/
│   │   ├── Pages/
│   │   │   └── Schemas/
│   │   │       └── PageForm.php
│   │   ├── Photos/
│   │   ├── Posts/
│   │   │   └── Schemas/
│   │   │       └── PostForm.php
│   │   ├── Projects/
│   │   ├── Roles/
│   │   ├── ServiceItems/
│   │   ├── Services/
│   │   │   └── Schemas/
│   │   │       └── ServiceForm.php
│   │   ├── SocialLinks/
│   │   ├── Testimonials/
│   │   ├── Users/
│   │   │   ├── Schemas/
│   │   │   │   └── UserForm.php
│   │   │   └── Tables/
│   │   │       └── UsersTable.php
│   │   └── Videos/
│   └── Resources/Users/UserResource.php
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── LoginController.php
│   │   ├── BlogController.php
│   │   ├── CabinetController.php
│   │   ├── HomeController.php
│   │   ├── MediaController.php        # Прокси-отдача оригиналов с удалённых дисков
│   │   ├── PortfolioController.php
│   │   ├── ServiceController.php
│   │   └── VideoController.php
│   └── Middleware/
├── Models/                     # Eloquent модели (17 шт.)
│   ├── Album.php
│   ├── Category.php
│   ├── FaqItem.php
│   ├── Inquiry.php
│   ├── Media.php
│   ├── NotificationSetting.php
│   ├── Page.php
│   ├── Photo.php
│   ├── Post.php
│   ├── Project.php
│   ├── Role.php
│   ├── Service.php
│   ├── ServiceItem.php
│   ├── SocialLink.php
│   ├── Testimonial.php
│   ├── User.php
│   └── Video.php
├── Observers/
│   ├── InquiryObserver.php       # Диспатч SendInquiryNotifications в очередь
│   ├── MediaObserver.php         # Тонкий: disk по умолчанию + dispatch ProcessMedia
│   └── PageObserver.php          # Сброс кэша PageContentService
├── Services/
│   ├── ImageCacheService.php     # Кэш производных изображений (display/lightbox) + вытеснение по лимиту
│   ├── MediaProcessor.php        # Централизованная обработка Media: metadata + WebP thumbnail
│   ├── PageContentService.php    # Кэшируемый сервис получения страниц
│   └── TelegramNotifier.php      # Отправка через Telegram API
└── Providers/
    └── Filament/
        └── AdminPanelProvider.php
```

## Публичные страницы (Blade)

```
resources/views/
├── layouts/
│   └── site.blade.php          # Базовый layout (header, footer, @vite)
├── components/site/
│   ├── header.blade.php        # Шапка (меню, auth-условные ссылки, бургер)
│   ├── footer.blade.php        # Подвал (контакты, политика)
│   ├── inquiry-form.blade.php  # Форма заявки
│   ├── inquiry-modal.blade.php # Модальное окно заявки
│   ├── share-button.blade.php  # Кнопка шаринга
│   ├── social-links.blade.php  # Иконки соцсетей
│   └── videos.blade.php        # Блок видео
├── emails/
│   └── new-inquiry.blade.php   # Шаблон письма о новой заявке
├── home.blade.php              # Главная (hero, услуги, портфолио, отзывы, блог, форма)
├── blog/
│   ├── index.blade.php         # Список статей (сетка 2 колонки, сайдбар, пагинация)
│   └── show.blade.php          # Детальная (контент, альбомы-слайдер, форма заявки)
├── portfolio/
│   ├── index.blade.php         # Сетка альбомов (masonry, fadeInUp)
│   └── show.blade.php          # Фотоальбом (lightbox, услуги, форма заявки)
├── services/
│   ├── index.blade.php         # Услуги по категориям (блоки с items, CTA)
│   └── show.blade.php          # Детальная (items, альбомы-примеры, форма)
├── video/
│   └── index.blade.php         # Раздел видео (горизонтальные + вертикальные)
├── cabinet/
│   └── index.blade.php         # Личный кабинет (заглушка)
└── auth/
    └── login.blade.php         # Страница входа
```

## Маршруты

| Метод | URI | Контроллер | Middleware |
|-------|-----|-----------|-----------|
| GET | `/` | `HomeController` | — |
| GET | `/services` | `ServiceController@index` | — |
| GET | `/services/{slug}` | `ServiceController@show` | — |
| GET | `/portfolio` | `PortfolioController@index` | — |
| GET | `/portfolio/{slug}` | `PortfolioController@show` | — |
| GET | `/blog` | `BlogController@index` | — |
| GET | `/blog/{slug}` | `BlogController@show` | — |
| GET | `/video` | `VideoController@index` | — |
| GET | `/media/{media}/original` | `MediaController@original` | — |
| GET | `/media/{media}/download` | `MediaController@download` | attachment |
| GET | `/media/{media}/display` | `MediaController@display` | PNG ≤800px из кэша |
| GET | `/media/{media}/lightbox` | `MediaController@lightbox` | PNG ≤1600px из кэша |
| POST | `/inquiry` | `HomeController@storeInquiry` | — |
| GET | `/cabinet` | `CabinetController@index` | `auth` |
| GET | `/login` | `Auth\LoginController@create` | `guest` |
| POST | `/login` | `Auth\LoginController@store` | `guest` |
| POST | `/logout` | `Auth\LoginController@destroy` | `auth` |

## Blade Layout и условный Vite

- Базовый layout `layouts/site.blade.php`
- `@vite()` загружается только при наличии `manifest.json` или `hot` файла
- Условная загрузка позволяет тестам работать без Vite-билда

## Шапка (Header)

- Пункты меню загружаются из таблицы `pages` через `PageContentService::getMenuItems()`
- Название: `menu_title` (если заполнено), иначе `title`
- URL формируется по slug (`home` → `/`, остальные → `/{slug}`)
- Для гостя: пункты из страниц + Оставить заявку + Войти
- Для авторизованного: + Личный кабинет, — Войти
- Для администратора: + Админка (/admin)
- Бургер-меню на мобильных (vanilla JS)
- Передача данных: `ViewComposerServiceProvider` (View::share) для всех views, использующих layout `layouts.site`
  - `menuItems` — пункты меню из страниц
  - `socialLinks` — активные ссылки на соцсети
  - `serviceList` — опубликованные услуги (для формы заявки)

## Action-классы (`app/Actions/`)

Инкапсулируют бизнес-логику, вынесенную из контроллеров / Filament-страниц.
Позволяют:
- тестировать логику независимо;
- переиспользовать в разных точках входа (HTTP, CLI, API).

Пример: `CreateAlbum` — транзакция создания альбома с медиа и фото.

### PageContentService (`app/Services/`)

Кэшируемый сервис для доступа к контенту страниц:

| Метод            | Кэш                  | Назначение                                |
|------------------|----------------------|-------------------------------------------|
| `get(slug)`      | `page_content_{slug}` | Опубликованная страница по slug           |
| `getHomeSections`| `pages_home_sections` | Все страницы с show_on_home = true        |
| `getMenuItems`   | `pages_menu`          | Пункты меню (menu_title ?? title)         |
| `clearCache(slug?)` | —                  | Сброс кэша (одной страницы или всего)     |

Кэш инвалидируется `PageObserver` при сохранении или удалении страницы.

## Наблюдатели (`app/Observers/`)

`MediaObserver` — минимальная привязка жизненного цикла Media к Eloquent:

- `creating` — гарантирует `disk` (по умолчанию `filesystems.default_media_disk`);
- `created` — однократно диспатчит Job `ProcessMedia` (после вставки записи).

Вся обработка файлов выполняется асинхронно в очереди; Observer бизнес-логики не содержит.

## ProcessMedia (`app/Jobs/ProcessMedia.php`) — этап B4

Тяжёлая обработка фотографий выполняется вне HTTP-запроса:

```text
Filament upload / Action
    ↓
Media created → Observer.created
    ↓
ProcessMedia::dispatch(mediaId)   [afterCommit]
    ↓
очередь: original → metadata → thumbnail
    ↓
ready
```

- Параметры: `$tries = 3`, `$timeout = 180` сек, `backoff() = [30, 120]` сек,
  `$afterCommit = true`.
- Job получает `mediaId` (int), а не модель: каждая попытка читает свежее
  состояние из БД; удалённая между dispatch и выполнением Media не роняет job.
- Dispatch происходит в единственной точке — `MediaObserver::created`, после
  успешного создания записи; покрывает все пути создания Media
  (Filament UploadPhotos/CreateAlbum, EditAlbum, ImportFromYandexDisk,
  MediaResource). Массовая загрузка даёт по одному job на каждый Media,
  HTTP-запрос не ждёт обработки.
- Транзакции: `afterCommit` гарантирует, что job попадает в очередь только
  после commit; при rollback (например, ошибка в `CreateAlbum`) задание не
  создаётся и не пытается обработать несуществующую Media.
- Идемпотентность обеспечивается `MediaProcessor` (детерминированный путь
  thumbnail, заполнение только пустых полей): повторное выполнение job не
  создаёт дубликатов thumbnail/Media и лишних записей в БД.

### Обработка ошибок в Job

| Ситуация                                   | Поведение                                              |
|--------------------------------------------|--------------------------------------------------------|
| Media отсутствует в БД                      | warning, job завершается без ошибки                    |
| Оригинал отсутствует / повреждён            | warning от процессора (`process()` → false), без retry |
| Storage недоступен (временная ошибка)       | Throwable пробрасывается → retry очереди с backoff     |

## MediaProcessor (`app/Services/MediaProcessor.php`)

Централизованная точка обработки Media. Единый lifecycle для Queue Job
(`ProcessMedia`) и команды `media:regenerate-thumbnails`:

```text
ProcessMedia (queue worker)
    ↓
сохранение оригинала — выполнено вызывающим кодом (Filament Upload, Action)
    ↓
MediaProcessor::processOrFail(Media)
    ├── определение MIME (mime_content_type)
    ├── file_size
    ├── width/height для изображений (getimagesize)
    ├── создание WebP-thumbnail 400px → диск `thumbnails`
    └── сохранение изменённых полей
    ↓
готово
```

- `process()` — логирует сбой storage и возвращает `false` (CLI, команда регенерации).
- `processOrFail()` — то же, но Throwable пробрасывается после логирования:
  временные сбои storage приводят к retry очереди.

- Оригинал читается с диска `Media::disk` через Laravel Filesystem (стримы,
  временный файл для GD — работает и с удалённым Яндекс.Диском).
- Thumbnail всегда пишется на локальный диск `thumbnails`; путь детерминирован:
  `{директория оригинала}/{имя}_thumb.webp`.
- Понятия разделены: original (`disk`, `file_path`) / thumbnail (`thumbnail_path`,
  диск `thumbnails`) / metadata (`mime_type`, `width`, `height`, `file_size`).

### Повторная обработка (идемпотентность)

- Заполнение только пустых полей metadata; непустые значения не перезаписываются.
- Существующий thumbnail не пересоздаётся, если файл есть на диске `thumbnails`;
  пересоздаётся при отсутствии файла или `force = true` (команда регенерации).
- `file_path` и `disk` процессором никогда не изменяются.
- Полностью обработанное Media повторный вызов не изменяет вовсе.

### Обработка ошибок

Ошибки логируются с контекстом (`media_id`, `disk`, `path`), возвращают `false`,
не приводят к потере уже известных данных и не меняют запись разрушающе:

| Ситуация                        | process()                     | processOrFail()            |
|---------------------------------|-------------------------------|----------------------------|
| Отсутствует оригинал            | warning, запись без изменений | warning                    |
| Файл нельзя прочитать           | warning, запись без изменений | warning                    |
| Изображение повреждено          | warning; mime/size сохраняются| warning                    |
| Невозможно создать thumbnail    | warning; метаданные сохраняются | warning                  |
| Storage недоступен (Throwable)  | error, false                  | error + throw (retry)      |

Статусы обработки в БД не вводятся: «требует обработки» выводится из пустых
полей и отсутствия файла thumbnail; повторный вызов `process()` безопасен.

## Команда media:regenerate-thumbnails

Выбор записей для регенерации (`no thumbnail`, `broken path`, `file missing`,
`--force`) остался в команде; сама обработка делегирована `MediaProcessor::process(force: true)` —
единая реализация генерации превью без дублирования GD-кода.

## Filament Resources

Для каждой сущности создан Resource с:
- Form (Schema)
- Table (колонки, фильтры, bulk-actions)
- Поиск

Дополнительные страницы:
- `UploadPhotos` — массовая загрузка фото в альбом
- `EditAlbum` — дозагрузка фото

Дополнительные RelationManagers:
- `PhotosRelationManager` — сетка фото, перетаскивание, «Сделать обложкой»
- `VideosRelationManager` — управление видео в альбоме

### Auto-slug на формах

На формах Album, Page, Post, Service slug генерируется автоматически
через `Str::slug($state)` при вводе названия. При дубликате добавляется
числовой суффикс (`-1`, `-2`). Ручное редактирование прекращает автогенерацию.

## Система ролей и доступа

### Модель доступа

- Filament доступен только администраторам с активным статусом
- `User::canAccessPanel()` проверяет `status === 'active'` и `hasRole('admin')`
- `User` содержит методы: `isAdmin()`, `hasRole()`, `hasAnyRole()`, `hasAllRoles()`

### Защита системных ролей

- Поле `roles.is_system` (boolean, default true)
- Системные роли нельзя удалить (исключение `LogicException` на deleting)
- Системным ролям нельзя изменить `slug` (исключение `LogicException` на saving)
- В Filament форме slug отключается (disabled) для системных ролей
- Кнопка удаления скрыта на странице редактирования системной роли

### Пользовательские роли

Администратор может создавать произвольные роли (`is_system = false`):
retoucher, assistant, manager, designer и т.д.

### Команда создания администратора

`php artisan make:filament-user` — создаёт пользователя со статусом `active`
и автоматически назначает роль `admin` (создаёт её при необходимости).

### Пользовательский кабинет

- Маршрут: `GET /cabinet` (middleware `auth`)
- Контроллер: `CabinetController@index`
- Защищён middleware `auth`
- Временно выводит приветствие пользователя

### Аутентификация

- Стандартная Laravel-аутентификация (сессии)
- `POST /login`, `POST /logout`, `GET /login`
- `LoginController` с валидацией и редиректом в кабинет
- Структура маршрутов готова к подключению Breeze

## Frontend

- **Blade** — серверный рендеринг (все публичные страницы)
- **Vite** — сборка CSS (Tailwind v4)
- **Filament (Livewire)** — только админ-панель
- **Vanilla JS** — бургер-меню, lightbox, слайдер альбомов

Tailwind v4: `@source`-директивы вместо `safelist` для сканирования Blade-шаблонов.
Добавлен `@source '../../app'` для сканирования классов в контроллерах/моделях.

## Хранение файлов

```
storage/app/public/
├── images/          # Оригиналы изображений (disk: public)
├── thumbnails/      # WebP превью (400px) (disk: thumbnails)
└── ...              # Прочие файлы
```

- Laravel Filesystem
- Диск `public` (локальный) — для оригиналов (по умолчанию, `MEDIA_DISK`)
- Диск `thumbnails` (локальный) — для превью (WebP, 400px)
- Диск `yandex_disk` — оригиналы на Яндекс.Диске (этап B2)

### Диск yandex_disk

- Драйвер `yandex-disk` регистрируется в `AppServiceProvider::boot()` через `Storage::extend()`
- SDK: `impressiveweb/yandex-disk` + Flysystem v3 адаптер `impressiveweb/yandex-disk-flysystem`
  (форк arhitector/yandex; оригинальный пакет несовместим с PHP 8.4 и Flysystem 3)
- Адаптер обёрнут в `YandexDiskPaginatedAdapter`: базовый `listContents()` читал
  только первую страницу API (20 элементов) — теперь листинг идёт чанками по
  `_embedded.total` с offset-пагинацией (`PAGE_SIZE = 100`); deep-листинг остался
  на поведении вендора (не используется, см. ограничение ниже)
- OAuth-токен и параметры — только из env: `YANDEX_DISK_TOKEN`, `YANDEX_DISK_PATH_PREFIX`, `YANDEX_DISK_ROOT`
- Корневая директория (`YANDEX_DISK_ROOT`) применяется как path-prefix клиента:
  все пути диска относительны ей, бизнес-логика не знает абсолютных путей Диска
- Флаг `remote => true` в конфиге диска: Media с таким диском отдаются через
  прокси-роут `media.original` (стриминг через Laravel), публичные URL отсутствуют
- Проверка подключения: `php artisan media:test-storage` (mkdir → write → read → delete → rmdir)
- Ограничение API: промежуточные папки не создаются при загрузке автоматически;
  рекурсивный «deep»-листинг делает запрос на каждую подпапку и не пагинирован —
  использовать неглубокий `directories($path)`/`files($path)`; пути вида `.folder`
  не поддерживаются клиентом SDK

### Импорт альбома из Яндекс.Диска

- Страница Filament `/admin/albums/import-yandex` (кнопка в списке альбомов)
- Выбор папки: каскад из двух Select (верхний уровень → подпапка, списки
  кэшируются на 10 минут) либо ручной ввод пути; валидация существования папки
- Action `ImportAlbumFromYandexDisk`: фильтрация изображений по расширению,
  естественная сортировка по имени, лимит `filesystems.yandex_import.max_files`
  (env `YANDEX_IMPORT_MAX_FILES`, по умолчанию 500), обложка — первое фото
  (опционально), создание альбома/Media/Photo в одной транзакции
- Форма не запускает импорт синхронно: страница диспатчит Job
  `App\Jobs\ImportAlbumFromYandexDisk` (tries=3, timeout=300, backoff [30,120]),
  который выполняет тот же Action; ошибка листинга → rollback транзакции и retry,
  дубликаты альбомов исключены атомарностью
- Job реализует `ShouldBeUnique`: uniqueId = md5(disk|type|folder) — повторная
  отправка формы (двойной клик) с теми же параметрами схлопывается в один Job;
  блокировка держится до завершения обработки
- Оригиналы остаются на Яндекс.Диске (`Media.disk = 'yandex_disk'`),
  метаданные и превью генерируются асинхронно Job `ProcessMedia` по стримам

- MediaProcessor генерирует превью на диске `thumbnails` через стримы (без path())
- Media::getUrl() — URL оригинала через диск из Media::disk (для remote-дисков — прокси-роут)
- Media::getThumbnailUrl() — URL превью 400px (WebP) через диск `thumbnails`
- Media::getDisplayUrl() / getLightboxUrl() — прокси-роуты кэша производных (см. ниже)
- Video::source_url / embed_url — через конфиг `filesystems.default_media_disk`
- Storage symlink: `public/storage -> storage/app/public`

### Кэш производных изображений (display / lightbox)

- Диск `image_cache` (локальный, `storage/app/image-cache`), параметры в `filesystems.image_cache`
  (`tiers`: display = 800px, lightbox = 1600px; `max_size_mb`, `png_level`; env `IMAGE_CACHE_DISK`, `IMAGE_CACHE_MAX_MB`)
- **Прогрев через очередь**: `ProcessMedia` после метаданных и WebP-thumbnail
  генерирует display + lightbox для изображений (`MediaProcessor::warmImageCache`),
  переиспользуя уже скачанный temp-файл оригинала — повторного запроса к
  Яндекс.Диску нет. Пропущенные варианты досчитываются при retry:
  `needsProcessing()` считает отсутствие любого варианта незавершённой обработкой
- Сервис `ImageCacheService`: ленивая генерация PNG осталась как fallback
  (первый запрос `media.display` / `media.lightbox`) — на случай вытеснения LRU,
  очистки командой или отставания воркера; пути детерминированы, поэтому файлы
  прогрева и fallback совпадают
  ключ файла: `{tier}/{media_id}-{sha1(id|tier|disk|path)[0..12]}.png`; повторные
  запросы отдаются с диска (`Cache-Control: immutable`)
- Источник — оригинал с любого диска (включая Яндекс.Диск) через временную копию;
- после генерации проверяется лимит размера кэша и при превышении вытесняются самые старые файлы;
  сбой LRU-обрезки не считается сбоем генерации (best-effort)
- Ручное управление: `php artisan media:prune-image-cache [--stats|--all]`
- Страница альбома `/portfolio/{slug}`: сетка — `media.display`, lightbox — `media.lightbox`,
  кнопка «Скачать в оригинальном разрешении» — `media.download` (attachment)

### Будущее
- Перенос локальных оригиналов на Яндекс.Диск (команда миграции)
- Локальный кэш превью остаётся на диске `thumbnails`
- Абстракция через драйверы Laravel Filesystem — бизнес-логика не привязана к конкретному диску

## База данных

См. `database.md` — 18 бизнес-таблиц + 7 pivot-таблиц + служебные.

### Пivot-таблицы
- `service_service_item` — услуги ↔ пункты (с полями `is_included`, `sort_order`)
- `album_service` — услуги ↔ альбомы-примеры работ
- `album_video` — альбомы ↔ видео (с полями `caption`, `sort_order`)
- `service_video` — услуги ↔ видео
- `post_video` — статьи ↔ видео
- `page_album` — страницы ↔ альбомы
- `post_album` — статьи ↔ альбомы

### ServiceItems — мастер-справочник
`service_items` — независимая таблица без FK, связь с услугами через
BelongsToMany + pivot. Позволяет переиспользовать пункты в разных услугах.

## Тестирование

| Уровень  | Расположение                       | Запуск          |
|----------|------------------------------------|-----------------|
| Unit     | `tests/Unit/Models/`               | `php artisan test` |
| Unit     | `tests/Unit/Services/`             | `php artisan test` |
| Unit     | `tests/Unit/Observers/`            | `php artisan test` |
| Feature  | `tests/Feature/`                   | `php artisan test` |

- БД: SQLite in-memory
- RefreshDatabase для тестов, изменяющих БД
- MediaProcessor: metadata, dimensions, thumbnail, идемпотентность, ошибки
  (`tests/Unit/Services/MediaProcessorTest.php`, remote-stream —
  `tests/Feature/Services/MediaProcessorRemoteStreamTest.php`)
- ProcessMedia Job: dispatch после commit, идемпотентность, отсутствие Media,
  ошибки обработки, retry временного сбоя storage
  (`tests/Feature/Jobs/ProcessMediaTest.php`)
- Пагинация листинга Яндекс.Диска (чанки по 100, `_embedded.total`):
  `tests/Unit/Filesystem/YandexDiskPaginationTest.php`
- Импорт альбома из очереди (Job переиспользует Action, dispatch со страницы,
  ShouldBeUnique против двойной отправки):
  `tests/Feature/Jobs/ImportAlbumFromYandexDiskJobTest.php`,
  `tests/Feature/Filament/ImportFromYandexDiskPageTest.php`
- Lifecycle Media (создание/обновление/удаление): `tests/Feature/Models/MediaLifecycleTest.php`
- PageContentService с кэшированием (get, getHomeSections, getMenuItems, clearCache)
- PageObserver — сброс кэша при сохранении/удалении страницы
- ViewComposerServiceProvider — передача menuItems в шапку
- 383 теста, 754 утверждения
- CI: `php artisan config:clear && php artisan test`

### Очередь и деплой

- `QUEUE_CONNECTION=database`, воркер: `php artisan queue:work` (в dev — `composer dev`)
- Воркер держит код в памяти: после обновления кода обязателен
  `php artisan queue:restart`, иначе Job'ы выполняются старой версией классов

## Ключевые решения

### Album.type (вместо отдельных таблиц)
Введено поле `type` (VARCHAR 20) вместо создания отдельных таблиц
для портфолио, клиентских галерей, слайдеров и т.д.
Простое и гибкое решение без избыточной нормализации.

### MediaObserver + ProcessMedia Job вместо логики в Observer
Обработка Media централизована в `MediaProcessor` (metadata, размеры, WebP-превью).
Observer только задаёт `disk` по умолчанию и диспатчит Job `ProcessMedia` при создании —
обработка асинхронна (этап B4), идемпотентна и доступна для повторного вызова
(команда регенерации, повторный dispatch).

### Action-классы вместо логики в Pages
Бизнес-логика создания альбома вынесена в `CreateAlbum` Action
для тестируемости и переиспользования.

### service_items как мастер-справочник
Вместо вложенных JSON или копирования пунктов в каждую услугу —
отдельная таблица `service_items` с BelongsToMany + pivot (`is_included`, `sort_order`).

### Auto-slug с уникализацией
Slug генерируется уникальным сразу (base + `-N`), вместо `unique`-валидации с ошибкой.
Ручное редактирование slug отключает автогенерацию.

### Условный @vite в тестах
`@vite()` загружается только при наличии `manifest.json` или `hot` файла,
чтобы тесты проходили без Vite-билда.

### Контент страниц из БД
Вместо хардкода в Blade — контент (заголовки, подзаголовки, SEO) хранится
в таблице `pages` и передаётся в шаблоны через `PageContentService` с кэшированием.
Меню сайта строится динамически из тех же записей.
Контент блоков (услуги, альбомы, статьи, отзывы) продолжает загружаться
из соответствующих моделей.

## Правила разработки

- strict_types рекомендуется
- PSR-12 (Laravel Pint)
- Eloquent Relationships (belongsTo, hasMany, belongsToMany)
- Избегать JSON-полей для связанных данных
- Не добавлять зависимости без необходимости
- Обновлять `database.md` при изменении схемы БД
- Обновлять `architecture.md` при изменении архитектуры
- Обновлять `changelog.md` после каждой задачи
