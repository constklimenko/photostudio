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
│   ├── Inquiry/
│   │   └── CreateProjectFromInquiry.php  # Транзакция: заявка → проект
│   └── Media/
│       ├── CheckMediaIntegrity.php         # Проверка целостности одного Media (B9)
│       ├── DeleteMedia.php               # Политика удаления Media и оригиналов (B6)
│       ├── MediaCheckResult.php          # Результат проверки целостности (B9)
│       ├── MediaMigrationResult.php      # Результат миграции одного Media (B8)
│       └── MigrateMediaToYandexDisk.php  # Миграция оригинала на Яндекс.Диск (B8)
├── Console/
│   └── Commands/
│       ├── MakeFilamentUser.php
│       ├── MediaCheck.php                  # Проверка целостности + orphan-файлы (B9)
│       ├── MediaMigrateToYandex.php      # Миграция локальных оригиналов на Диск (B8)
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
│   │   ├── Icons/
│   │   ├── Inquiries/
│   │   │   └── Schemas/
│   │   │       └── InquiryForm.php
│   │   ├── Media/
│   │   │   ├── Pages/
│   │   │   │   ├── CreateMedia.php
│   │   │   │   ├── EditMedia.php     # Удаление (2 кнопки для Yandex) + повторная обработка
│   │   │   │   └── ListMedia.php
│   │   │   ├── Schemas/
│   │   │   │   └── MediaForm.php
│   │   │   └── Tables/
│   │   │       └── MediaTable.php    # Bulk delete с Да/Нет, сводка ошибок, retry, статус обработки
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
│   │   ├── ServiceCatalogController.php  # Иерархический каталог услуг (B11)
│   │   └── VideoController.php
│   └── Middleware/
├── Models/                     # Eloquent модели (18 шт.)
│   ├── Album.php
│   ├── Category.php
│   ├── FaqItem.php
│   ├── Icon.php
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
│   ├── ServiceCatalogResolver.php # Разрешение иерархического пути /services (B11)
│   ├── CategoryTreeService.php    # Дерево категорий услуг для админки (B11): flatten/options/move
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
│   ├── album-photos.blade.php # Сетка фото альбома + lightbox (страница альбома и блок в услуге)
│   ├── breadcrumbs.blade.php     # Переиспользуемые хлебные крошки <x-site.breadcrumbs/>
│   ├── header.blade.php          # Шапка (меню, auth-условные ссылки, бургер)
│   ├── footer.blade.php        # Подвал (контакты, политика)
│   ├── inquiry-form.blade.php  # Форма заявки
│   ├── inquiry-modal.blade.php # Модальное окно заявки
│   ├── share-button.blade.php  # Кнопка шаринга
│   ├── social-links.blade.php  # Иконки соцсетей
│   ├── video-player.blade.php  # Плеер видео: поворот ±90°, запрет скачивания, кастомные контролы
│   └── videos.blade.php        # Блок видео (через x-site.video-player) (через x-site.video-player)
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
│   ├── index.blade.php         # Каталог услуг: корневые категории + услуги без категории (B11)
│   ├── category.blade.php      # Страница категории: title, cover, описание, цена, дети, услуги, альбомы-примеры, видео, форма (B11)
│   └── show.blade.php          # Детальная услуги (items, альбомы-примеры, видео, breadcrumbs, форма); опционально блок фото выбранного альбома
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
| GET | `/services` | `ServiceCatalogController@index` | — |
| GET | `/services/{path}` | `ServiceCatalogController@show` (path = категории/…/услуга) | — |
| GET | `/portfolio` | `PortfolioController@index` | — |
| GET | `/portfolio/{slug}` | `PortfolioController@show` | — |
| GET | `/blog` | `BlogController@index` | — |
| GET | `/blog/{slug}` | `BlogController@show` | — |
| GET | `/video` | `VideoController@index` | — |
| GET | `/video/{video}/stream` | `VideoController@stream` | raw |
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

Примеры: `CreateAlbum` — транзакция создания альбома с медиа и фото;
`DeleteMedia` — политика удаления Media и физических оригиналов (этап B6).

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

## Проверка целостности — media:check — этап B9

Команда `php artisan media:check` проверяет целостность Media Storage
и обнаруживает potential orphan-файлы на Яндекс.Диске.

**Команда ничего не удаляет по умолчанию.**

### Проверка DB → Storage

Для каждого Media record:

| Что проверяется | Как | Статус |
|-----------------|-----|--------|
| Оригинал существует | `Storage::disk($media->disk)->exists($path)` | `missing_original` |
| Thumbnail существует (для изображений) | `Storage::disk('thumbnails')->exists($thumbnail_path)` | `missing_thumbnail` |
| Кэш display/lightbox (для изображений) | `ImageCacheService::isCached()` по tiers | `missing_image_cache` |
| Metadata: file_size, dimensions | Наличие и адекватность значений | `metadata_mismatch` |
| File size vs disk (локальные файлы) | readStream → temp → filesize | `metadata_mismatch` |
| Всё корректно | — | `valid` |

Для remote-дисков (Яндекс.Диск) file size проверяется через HEAD/size (быстро),
полное скачивание НЕ выполняется.

### Проверка Yandex → DB (orphan-файлы)

Сканируются все файлы на `yandex_disk` через `allFiles()`.
Для каждого файла проверяется наличие записи Media с `disk = 'yandex_disk'`
и соответствующим `file_path`.

Файлы без соответствующей записи Media классифицируются как
**potential orphans** — НЕ как ошибки.

### Почему orphan НЕ удаляются автоматически

Пользователь может удалить Media через политику B6, выбрав
«Не удалять файл с Яндекс-Диска». В этом случае файл намеренно
остаётся на Диске. Автоматическая очистка привела бы к потере данных.

В будущем можно реализовать отдельную команду очистки orphan-файлов
с явным подтверждением пользователя.

### Опции команды

```
media:check [--fix-thumbnails] [--media-id=ID] [--limit=N]
```

- `--fix-thumbnails` — восстанавливает отсутствующие thumbnails
  через `MediaProcessor::process(force: true)`. Не затрагивает originals.
  Не восстанавливает orphan Yandex-оригиналы.
- `--media-id=ID` — проверить конкретный Media record.
- `--limit=N` — ограничить количество проверяемых записей.

### Файлы

- `app/Actions/Media/CheckMediaIntegrity.php` — проверка одного Media
- `app/Actions/Media/MediaCheckResult.php` — результат проверки
- `app/Console/Commands/MediaCheck.php` — Artisan-команда

## Удаление Media — DeleteMedia (`app/Actions/Media/DeleteMedia.php`) — этапы B6/B7

Удаление записи Media не всегда означает удаление оригинального файла.
Единая точка политики — Action `DeleteMedia::execute(Media, bool $deleteRemoteOriginal)`:

```text
локальная Media:  удалить local original → удалить derivatives → удалить запись
удалённая + «Да»: удалить remote original → удалить derivatives → удалить запись
удалённая + «Нет»: ОСТАВИТЬ original (orphan) → удалить derivatives → удалить запись
```

- Диск считается удалённым через `Media::isRemoteDisk()` (флаг `remote => true`
  в конфиге диска), а не по имени — политика работает с любым диском.
- Derivatives (WebP-thumbnail на `thumbnails` + display/lightbox на `image_cache`)
  удаляются всегда; сбой удаления производной логируется warning'ом и не блокирует
  удаление записи — кэш несущественен, потеря записи недопустима из-за него.
- **Критическое правило**: если запрошенный к удалению оригинал удалить не удалось
  (Диск недоступен и т.п.) — запись Media сохраняется, ошибка логируется,
  производные не трогаются. Сценарий «записи нет, файл есть» системой не создаётся.
- Отказ от удаления Yandex-оригинала оставляет файл как потенциальный orphan;
  автоматическая очистка orphan-файлов запрещена (обнаружение — задача
  команды проверки целостности).
- Filament-интеграция (доведена до финала в этапе B7):
  - одиночное удаление (`EditMedia`) — для локальных дисков стандартное
    подтверждение «будут удалены запись и все файлы»; для remote-дисков модалка
    «Удалить файл с Яндекс-Диска?» с двумя явными кнопками вместо checkbox:
    «Удалить Media, оставить файл» (по умолчанию, безопасный вариант) и
    «Удалить Media и файл» (danger), плюс стандартная «Отмена»; выбор передаётся
    аргументом действия, форма не используется;
  - массовое удаление (`MediaTable`) — одно подтверждение на всю выборку:
    описание показывает количество выбранных файлов и Yandex-оригиналов,
    выбор «Да / Нет» через Radio (виден только если среди выбранных есть
    оригиналы на Яндекс-Диске); один ответ применяется ко всей операции,
    значение по умолчанию — «Нет» (безопасное);
  - сводка результатов bulk: уведомление показывает удалено всего, сколько
    вместе с оригиналом на Яндекс-Диске, сколько с сохранением оригинала и
    сколько не удалено из-за ошибки; упавшие записи остаются в БД, stack trace
    пользователю не показывается (детали — в журнале ошибок);
  - смешанные сбои при bulk: каждая запись обрабатывается независимо.
- Прямой `$media->delete()` мимо Action удаляет только запись БД (контракт:
  физические файлы удаляет только `DeleteMedia`).

## Миграция локальных оригиналов на Яндекс.Диск — этап B8

Одноразовая (но повторяемая) операция перевода существующих локальных оригиналов
на схему «оригиналы на Диске, производные локально». Логика одной записи —
Action `MigrateMediaToYandexDisk` (`app/Actions/Media/`); выборка, batching,
вывод и обработка ошибок — Artisan-команда `media:migrate-to-yandex`.

Порядок операций для одного Media — критическая последовательность:

```text
отбор кандидата (локальные проверки + наличие локального файла)
      ↓
upload (стрим во временную копию; при необходимости mkdir)
      ↓
verification: exists → size → sha256 содержимого
      ↓
update Media.disk = yandex_disk   ← только после проверки
      ↓
delete local original             ← только после обновления БД
```

- **Никогда** не удалять локальный файл до загрузки, проверки и обновления БД.
  Любой сбой оставляет локальный оригинал на месте.
- **Идемпотентность**: Media с `disk = yandex_disk` пропускаются; существующий
  на Диске файл не перезаписывается — при совпадении размера и sha256 он
  переиспользуется (без повторной загрузки), при расхождении миграция падает
  с ошибкой конфликта. Неудачная собственная загрузка (провал верификации)
  удаляется с Диска, чтобы повторный запуск прошёл начисто; чужой файл
  по занятому пути никогда не трогается.
- **Изоляция сбоев**: ошибка одного Media не прерывает batch; итог — FAILURE,
  если были ошибки. Проблемы отбора (нет файла, не изображение и т.п.) — это
  Skip с причиной; Failed — только сбои upload/verification/DB-update.
  Сбой удаления локального файла после успешного update не откатывает миграцию:
  запись уже указывает на Диск, локальный остаток — безвредный дубликат.
- **Кандидаты**: только изображения (`image/*`), лежащие на дисках с драйвером
  `local`. Не мигрируются: записи уже на remote-дисках, производные
  (диски `thumbnails`, `image_cache`), неизвестные/чужие хранилища, записи без
  пути или MIME. Видео в Media отсутствуют (Video хранится отдельно).
- Удалённый путь = исходный `file_path` (детерминированный, без переименований).
- Ключ кэша display/lightbox включает disk: старые варианты удаляются до
  смены `disk` (best-effort); новые генерируются лениво или через
  «Повторить обработку». Thumbnail остаётся валидным (путь не меняется).
- После миграции удаление такой Media идёт по политике B6: пользователь решает,
  удалять ли Yandex-оригинал.

CLI: `php artisan media:migrate-to-yandex [--dry-run] [--limit=N] [--media-id=ID]`
(`--limit` применяется после отбора кандидатов; записи сверх лимита учитываются
как пропущенные). Dry-run ничего не пишет ни в БД, ни в storage; проверка
удалённого конфликта в dry-run — по метаданным размера, без скачивания.

## Статус обработки и повторная обработка Media — этап B7

Отдельное поле `status` в БД не введено (осознанно): состояние обработки
выводится из данных. Публичный API — `MediaProcessor::isPending(Media)`:

```text
isPending = пустые метаданные (mime/size/width/height)
          || отсутствует файл thumbnail
          || отсутствует display/lightbox вариант кэша
```

## Поворот фото — RotateMedia (`app/Actions/Media/RotateMedia.php`)

Единая точка поворота оригинала изображения из админки (альбом → фотография):

```text
Filament «Повернуть» (90/180/270° CW)
    ↓
RotateMedia::execute(Media, degrees)
    ├── чтение оригинала с Media::disk (Filesystem)
    ├── GD-поворот (JPEG/PNG/WebP; только кратно 90°)
    ├── перезапись повёрнутого оригинала на том же диске
    ├── обновление width / height / file_size
    ├── ImageCacheService::forget  (сброс устаревшего кэша)
    └── MediaProcessor::process(force: true)  (WebP-thumbnail + display/lightbox)
```

Правила:

- оригинал перезаписывается на исходном `disk`/`file_path` — идемпотентности
  нет (повторный вызов = ещё один поворот), но производные пересобираются
  детерминированно;
- производные (thumbnail и image-cache) регенерируются принудительно и
  синхронно, устаревшие варианты кэша удаляются до пересборки;
- сбой на любом шаге не теряет запись Media — ошибки логируются с контекстом;
- неподдерживаемые форматы (GIF и др.), некратные 90° углы и отсутствующие
  файлы возвращают `false` без изменений;
- в `PhotosRelationManager` изменение `title` фотографии редактирует
  `media.title` через отдельное действие «Редактировать».

Filament-UX (`MediaTable`, `EditMedia`):

- колонка «Обработка» в списке Media: «Готово» / «В очереди» — вычисляется
  через `isPending`, без запросов состояния из БД;
- действие «Повторить обработку» (запись списка + страница редактирования):
  видно только для незавершённой обработки; повторно диспатчит Job
  `ProcessMedia` (логика обработки не дублируется — идемпотентность
  обеспечивает `MediaProcessor`), HTTP-запрос тяжёлой работы не выполняет.

Загрузка (single upload в `MediaResource`, массовая загрузка фото в альбом,
импорт папки с Яндекс.Диска) не ждёт обработки в HTTP-запросе: файлы
сохраняются синхронно, обработка — асинхронно через очередь (этап B4).

Удаление Photo не затрагивает Media (Media переиспользуемы); удаление Media
каскадно убирает зависимые Photo (FK `photos.media_id`) и обнуляет ссылки
обложек (`nullOnDelete`); удаление Album сохраняет Media.

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

### Плеер видео: поворот ±90° и запрет скачивания

- **Роут `GET /video/{video}/stream`** — сырой поток загруженного видео через
  `VideoController@stream` (StreamedResponse, диск `filesystems.default_media_disk`).
  Заголовки: `Content-Type: video/mp4`, `Content-Disposition: inline`,
  `Cache-Control: private, max-age=86400, immutable`, `X-Content-Type-Options: nosniff`,
  `Accept-Ranges: bytes`, `ETag`. 404 при отсутствии `file_path`/файла.
- **Кэширование браузером**: `private max-age` позволяет хранить видео в
  кэше конкретного браузера (недоступно для CDN/прокси/промежуточных узлов);
  ETag + `If-None-Match` дают дешёвую ревалидацию (304). File не перезаписывается
  по содержимому, поэтому `immutable` оправдан.
- **Поддержка HTTP Range (206 Partial Content)** для мгновенного старта
  воспроизведения HTML5 `<video>` без ожидания всего файла (~41 МБ):
  одиночные `bytes=start-end`, открытые (`bytes=n-`), суффиксные (`bytes=-n`)
  и множественные (`bytes=a-b,c-d` → `multipart/byteranges`) диапазоны;
  `If-None-Match` → `304`, неудовлетворимый диапазон → `416` с
  `Content-Range: bytes */size`. Стрим читается локально
  (`fopen` + `fseek`/`fread` чанками 8 КБ), между запросами полнотелого 200 и
  сегмента 206 выставляются `Content-Length`/`Content-Range`.
- **`Video::source_url`** указывает на `video.stream` (прокси вместо `Storage::url`),
  чтобы не публиковать реальный путь файла напрямую.
- **Компонент `x-site.video-player`** (`resources/views/components/site/video-player.blade.php`)
  — единый рендер видео на страницах сайта (главная, раздел `/video`,
  блоки в услугах/статьях/альбомах через `x-site.videos`):
  - embed-видео → iframe;
  - загруженный файл без поворота → нативный `<video>` c `controls`;
  - загруженный файл с поворотом → `<video>` без нативных контрол +
    кастомный плеер (большая кнопка Play/Pause, прогресс-бар, таймкод),
    инициализируемый JS-хендлером `[data-video-player]` в `resources/js/app.js`.
- **Поворот визуальный (CSS-transform)**, файл на диске не изменяется:
  для вписывания 9:16 → 16:9 в ландшафтный контейнер `aspect-video`
  применены `width:56.25%`, `height:177.78%`, `object-fit:cover`,
  `transform:translate(-50%,-50%) rotate(±90deg)`. Управляется полем `Video.rotation`
  (`0` / `90` / `-90`); контейнер повёрнутого видео — `aspect-video`, неповёрнутого
  вертикального — `aspect-[9/16]`. Прямой `/video/{id}/stream` отдаёт файл без поворота.
- **Запрет скачивания (затруднение, не 100% защита)**: прокси-роут с
  `Content-Disposition: inline` и приватным кэшем; на `<video>` —
  `controlsList="nodownload noremoteplayback"`, `disablepictureinpicture`,
  `oncontextmenu="return false"`.
- **Ленивая подгрузка**: `preload="auto"` — браузер сразу после загрузки
  страницы подтягивает метаданные и часть видео (на странице не больше 3 видео),
  поэтому воспроизведение начинается мгновенно; скачанные байты остаются в
  браузерном кэше (см. выше) и повторное открытие не перекачивает файл.
- **Звук управляется только в админке** через поле `Video.has_sound`. Когда звук
  отключён (`false`), возможность включить звук недоступна в любом типе плеера:
  - для загруженных видео (повёрнутых и обычных) на `<video>` добавляется
    `muted` + `data-video-forbid-sound`; JS в `resources/js/app.js` принудительно
    держит `video.muted = true` и повторно приглушает на `volumechange`/`play`/
    `loadedmetadata`, поэтому кнопка mute/громкость нативных контролов не даёт
    эффекта (у обычных видео к `controlsList` добавлен `noplaybackrate`);
  - для встраиваемых видео `Video.embed_url` добавляет параметр приглушения:
    YouTube `?mute=1`, Vimeo/Rutube `?muted=1`, VK (`video_ext.php`) `&muted=1`.
  Кнопки включения звука в кастомном плеере нет.

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
- ~~Перенос локальных originals на Яндекс.Диск~~ — реализовано командой
  `media:migrate-to-yandex` (этап B8)
- Локальный кэш превью остаётся на диске `thumbnails`
- Абстракция через драйверы Laravel Filesystem — бизнес-логика не привязана к конкретному диску

### Итоговая архитектура Media Storage (этап B10)

Ключевые правила системы хранения и обработки медиа:

1. **`Media.disk` определяет хранилище оригинала.** Диск задаётся при создании Media
   (по умолчанию из `filesystems.default_media_disk`) и может быть `public` (локальный)
   или `yandex_disk` (удалённый). Производные (thumbnail, display/lightbox) всегда
   хранятся локально независимо от диска оригинала.

2. **Производные изображения хранятся локально.** WebP-превью (400px) — на диске
   `thumbnails`; PNG-кэш display (800px) и lightbox (1600px) — на диске `image_cache`.
   Публичные страницы никогда не обращаются к Яндекс.Диску напрямую.

3. **ProcessMedia выполняется через Queue.** Обработка (метаданные + превью + кэш)
   запускается однократно из `MediaObserver::created` с `afterCommit = true`.
   HTTP-запрос не выполняет тяжёлой работы. Retry: 3 попытки, backoff 30/120 сек.

4. **Удаление local original происходит автоматически.** При удалении Media
   с локального диска оригинал удаляется всегда (без подтверждения).

5. **Удаление Yandex original требует подтверждения.** Одиночное удаление
   — две явные кнопки в модалке (оставить / удалить); bulk — Radio Да/Нет
   с выбором по умолчанию «Нет».

6. **Пользователь может удалить Media, оставив Yandex original.** Запись БД
   удаляется, файл на Диске остаётся как потенциальный orphan.

7. **Оставшийся Yandex original является потенциальным orphan.** Автоматическая
   очистка orphan-файлов запрещена: пользователь мог сознательно оставить файл.

8. **`media:check` не удаляет orphan автоматически.** Команда только обнаруживает
   и классифицирует их как «Potential orphan files» (не ошибки).

9. **Migration local → Yandex является повторяемой.** Критическая последовательность:
   upload → verify (size + sha256) → DB update → delete local. Идемпотентность:
   `disk = yandex_disk` → skip; совпадающий remote-файл переиспользуется;
   расхождение → ошибка без изменения данных.

10. **Клиентское скачивание альбома — будущая функция, а не часть этапа B.**
    Текущая архитектура не мешает реализации: проверка прав → ссылка/операция
    скачивания папки с Яндекс.Диска → отдача архива напрямую (без проксирования
    через Laravel). Не реализовывать на этапе Media Storage.

## База данных

См. `database.md` — 18 бизнес-таблиц + 8 pivot-таблиц + служебные.

### Pivot-таблицы
- `service_service_item` — услуги ↔ пункты (с полями `is_included`, `sort_order`)
- `album_service` — услуги ↔ альбомы-примеры работ
- `album_video` — альбомы ↔ видео (с полями `caption`, `sort_order`)
- `album_user` — пользователи (`parent`) ↔ клиентские альбомы (C1.1)
- `service_video` — услуги ↔ видео
- `post_video` — статьи ↔ видео
- `page_album` — страницы ↔ альбомы
- `post_album` — статьи ↔ альбомы

### ServiceItems — мастер-справочник
`service_items` — независимая таблица, связь с услугами через
BelongsToMany + pivot. Позволяет переиспользовать пункты в разных услугах.
Пункты могут иметь иконку (`icons` FK) и подзаголовок (`subtitle`).

### Icons — иконки пунктов услуг
`icons` — справочник файловых иконок (SVG/PNG), хранимых локально
на диске `public` в директории `icons/`. Связь с `service_items` one-to-many.
Иконка опциональна: при наличии заменяет галочку/крестик в шаблоне.

### Каталог услуг — иерархия категорий (этап B11, часть 1)

Таблица `categories` (единая для услуг и блога) расширена полями иерархии:

- `parent_id` — self-referencing FK → `categories.id` (ON DELETE SET NULL),
  глубина дерева не ограничена;
- `cover_media_id` — обложка категории (FK → `media.id`, SET NULL);
- `description`, `price_from`, `price_note` — контент страницы каталога;
- `seo_title`, `seo_description` — SEO-метаданные страницы категории;
- `is_published` — публикация категории (default true).

Политика `type`: `service` — категории каталога услуг с произвольной вложенностью;
`post` — плоские категории блога (поведение не изменено).

Модель `Category` (`app/Models/Category.php`):

- отношения `parent()`, `children()`, `cover()`, а также существующие
  `services()`, `posts()`;
- методы `ancestors($withSelf = false)` (от корня к родителю), `path($withSelf = false)`
  (полный путь), `descendants()` (все потомки в глубину);
- **защита от циклов** на уровне модели: хук `saving` вызывает
  `assertNotCyclic()`, когда `parent_id` изменён. Запрещены выбор категории
  в качестве собственного родителя и цепочка вида `A → B → C → A`
  (проверка поднимается по цепочке предков нового родителя; глубина ограничена
  стражем итераций). Обход путей (`ancestors`/`descendants`) также защищён
  от зацикливания на битых данных.

Структура сущности:

```text
Category
├── parent      (BelongsTo, категорию можно перемещать)
├── children    (HasMany, произвольная вложенность)
├── cover       (BelongsTo → media)
├── services    (HasMany → услуги непосредственного уровня)
└── posts       (HasMany → статьи блога)
```

### Публичный каталог услуг — URL-резолвер и страницы (этап B11, часть 2)

Единый контроллер `ServiceCatalogController` (`app/Http/Controllers/`) обслуживает
все URL раздела услуг:

```text
/services                                    → index (корневые категории + услуги без категории)
/services/{category-path}                    → страница категории
/services/{category-path}/{service-slug}     → страница услуги
```

- Роут `GET /services/{path}` использует `where('path', '.*')` — путь принимается
  целиком и разбивается на сегменты в контроллере.
- **Разрешение пути — отдельный сервис `ServiceCatalogResolver`**
  (`app/Services/ServiceCatalogResolver.php`), метод `resolve(array $segments)`
  возвращает `Category | Service | null`.
- Сущность **не определяется по количеству сегментов**: каждый сегмент сначала
  проверяется как категория (`type = service`, `is_published`, `parent_id`
  ровно предыдущей категории, у корня `parent_id IS NULL`); если дочерней
  категории нет и сегмент последний — он разрешается как услуга категории
  (`is_published`). Так `/services/vypusknye-albomy/dlya-shkol` → Category,
  а `/services/vypusknye-albomy/dlya-shkol/klassika` → Service. Категория имеет
  приоритет при совпадении slug (детерминированно).
- Некорректная цепочка родителей, неопубликованная категория или услуга,
  обращение к категории блога (`type = post`) → `null` → 404.
- `abort_unless()` в контроллере превращает `null` в 404; сущность не может
  попасть в шаблоны без проверки типа.

Генерация URL:

- `Category::catalogPath()` — иерархический slug-путь категории (`parent/sub`);
- `Service::catalogPath()` — полный путь услуги с категориями (`parent/sub/service`),
  для услуги без категории — просто slug;
- шаблоны строят ссылки через `route('services.show', $model->catalogPath())`;
  единственное необходимое поле у `Service` для генерation — `category_id`
  (добавлено в выборку `HomeController` и `ServiceCatalogController`).

Страница категории (`services/category.blade.php`):

```text
breadcrumbs (Главная → Услуги → … → Категория)
cover (оригинал)
title
description
«Цена от XX ₽» + price_note
Разделы → дочерние опубликованные категории (карточки)
Варианты оформления → услуги непосредственного уровня (карточки)
CTA / форма заявки
SEO (seo_title / seo_description, фоллбэк на Page services)
```

Страница услуги (`services/show.blade.php`) сохраняет ранее реализованные
функции: цена, описание, обложка, ServiceItems, альбомы-примеры, видео,
форма заявки + полный иерархический breadcrumb через компонент.

### Альбом на странице услуги, отображаемый всеми фото

Дополнительная настройка услуги в админке (`ServiceForm`, раздел «Примеры работ»):

- `show_album_photos` — Toggle «Показать первый альбом блоком с фото»;
- `featured_album_id` — Select конкретного альбома (BelongsTo → `albums`,
  `ON DELETE SET NULL`).

Поведение публичной страницы услуги (`ServiceCatalogController::showService`):

- при `show_album_photos = true` выбранный альбом **исключается** из списка
  альбомов-примеров (карточек) и выводится ниже в виде сетки фотографий
  с lightbox — тем же переиспользуемым компонентом `<x-site.album-photos :album="…" />`,
  который используется на странице альбома `/portfolio/{slug}`;
- если после исключения в списке остаются альбомы (≥ 1), сначала рендерится
  секция «Примеры работ» с карточками, затем — блок фото выбранного альбома
  (заголовок блока — название альбома);
- неопубликованный `featured_album_id` игнорируется (блок не выводится);
- при выключенном toggle выбранный альбом остаётся обычной карточкой.

Аналогичная настройка реализована для категорий (`CategoryForm`, раздел
«Примеры работ», и `services/category.blade.php`): `show_album_photos` +
`featured_album_id` прикрепляют альбомы-примеры к категории через pivot
`category_album` (`Category::albums()` BelongsToMany) и позволяют вывести
выбранный альбом сеткой фотографий. Поведение полностью повторяет услуги
(`ServiceCatalogController::showCategory`).

Компонент `<x-site.album-photos>` расположил разметку сетки + lightbox и JS
в одном месте, устранив дублирование со страницей альбома.

Хлебные крошки — переиспользуемый компонент `<x-site.breadcrumbs :items="…" />`,
принимающий массив `['label' => …, 'url' => …]`; последний элемент без `url`
отображается как текущая страница. Используется на страницах категории и услуги.

Контроллер не содержит бизнес-логики разрешения: `index()` выбирает корневые
категории и услуги без категории, `show()` делегирует определение сущности
резолверу и рендерит соответствующий шаблон.

Filament-дерево категорий — следующая часть этапа B11.

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
- Политика удаления Media и оригиналов (Action + Filament bulk/single):
  `tests/Feature/Actions/DeleteMediaTest.php`,
  `tests/Feature/Filament/MediaDeletionTest.php`
- Миграция локальных оригиналов на Яндекс.Диск (upload → verify → DB → delete,
  идемпотентность, изоляция сбоев): `tests/Feature/Actions/MigrateMediaToYandexDiskTest.php`,
  `tests/Feature/Console/MediaMigrateToYandexCommandTest.php`
- Filament UX обработки Media (retry-действие, статус, single-upload,
  переиспользование Media при удалении Photo/Album):
  `tests/Feature/Filament/MediaRetryProcessingTest.php`,
  `tests/Feature/Filament/MediaUploadTest.php`,
  `tests/Feature/Models/MediaReuseSafetyTest.php`
- Проверка целостности Media и orphan-файлы (B9):
  `tests/Feature/Actions/MediaCheckTest.php`,
  `tests/Feature/Console/MediaCheckCommandTest.php`
- Иерархия категорий каталога услуг (B11, часть 1): parent/children, несколько
  уровней вложенности, category→services, cover_media, независимость service/post,
  защита от циклов: `tests/Feature/Models/CategoryHierarchyTest.php`
- Публичный каталог услуг (B11, часть 2) — корневые/вложенные категории, услуга
  во вложенной категории и на корневом уровне, приоритет категории над услугой,
  неправильная цепочка URL, неопубликованные категории/услуги, генерация
  иерархического URL, сохранение функционала страницы услуги и breadcrumb:
  `tests/Feature/Services/ServiceCatalogResolverTest.php`,
  `tests/Feature/Http/Controllers/ServiceCatalogControllerTest.php`
- PageContentService с кэшированием (get, getHomeSections, getMenuItems, clearCache)
- PageObserver — сброс кэша при сохранении/удалении страницы
- ViewComposerServiceProvider — передача menuItems в шапку
- 499 тестов, 1254 утверждений
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
