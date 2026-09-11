# Changelog

## 2026-09-11 — AR-тизер: акцент заголовка и нижняя строка

### Добавлено

- **database/migrations/2026_09_11_084636_add_ar_teaser_accent_and_footer_to_pages_table.php** —
  2 поля в таблицу `pages`:
  - `ar_teaser_accent` (string, nullable) — золотая строка под заголовком;
  - `ar_teaser_footer` (string, nullable) — строка «Следите за новостями».
- **app/Models/Page** — поля добавлены в `$fillable`.
- **app/Filament/Resources/Pages/Schemas/PageForm.php** — поля «Акцент
  заголовка» и «Нижняя строка» в секции «Оживающие фотографии».
- **app/Http/Controllers/HomeController** — `$arTeaser` дополнен ключами
  `accent` и `footer`.
- **resources/views/components/site/ar-teaser.blade.php** — пропсы `$accent`
  и `$footer` с fallback на дефолтные значения.

### Тесты

- `test_ar_teaser_title_and_subtitle_from_database` — включает проверку
  accent и footer.
- `test_ar_teaser_accent_and_footer_defaults_when_null` — дефолты при NULL.

### Проверка

- `php artisan test --filter=HomeControllerTest` — 30 passed / 71 assertions.
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-11 — AR-тизер: управление из CMS (Filament)

### Цель

Сделать основные параметры секции «Оживающие фотографии» на главной странице
управляемыми из админки Filament. Секция является частью контента страницы
`home`, поэтому управление находится в Контент → Страницы → Главная.

### Добавлено

- **database/migrations/2026_09_11_081548_add_ar_teaser_fields_to_pages_table.php** (новая) —
  4 поля в таблицу `pages`:
  - `ar_teaser_enabled` (boolean, default true);
  - `ar_teaser_title` (string, nullable);
  - `ar_teaser_subtitle` (text, nullable);
  - `ar_teaser_media_id` (FK → media, nullable, nullOnDelete).
- **app/Models/Page** — добавлены `ar_teaser_*` в `$fillable`, каст
  `ar_teaser_enabled` → boolean, связь `arTeaserMedia()`.
- **app/Filament/Resources/Pages/Schemas/PageForm.php** — секция
  «Оживающие фотографии» с toggle, TextInput, Textarea и Select media.
- **resources/views/components/site/ar-teaser.blade.php** — компонент
  принимает пропсы `$title`, `$subtitle`, `$media` с fallback на дефолтные
  значения. Изображение из Media или статический `images/ar-teaser.jpg`.

### Изменено

- **app/Http/Controllers/HomeController** — собирает массив `$arTeaser` из
  полей страницы `home` и передаёт в view.
- **resources/views/home.blade.php** — секция AR рендерится условно
  (`$arTeaser['enabled']`) с прокидыванием данных в компонент.

### Кэш

Инвалидация через существующий `PageObserver::saved` — при сохранении
страницы `home` автоматически очищается `page_content_home`. Отдельный
механизм кэширования не добавлялся.

### Тесты

- `test_home_page_renders_ar_teaser` — существующий тест (дефолтные значения).
- `test_ar_teaser_hidden_when_disabled` — секция скрыта при `ar_teaser_enabled = false`.
- `test_ar_teaser_title_and_subtitle_from_database` — заголовок и описание из БД.
- `test_ar_teaser_image_from_selected_media` — изображение из выбранного Media.
- `test_ar_teaser_without_media_shows_fallback` — fallback на статическое фото.
- `test_ar_teaser_default_values_when_fields_null` — дефолты при NULL.
- `test_ar_teaser_page_saved_clears_cache` — инвалидация кэша при изменении.

### Проверка

- `php artisan test --filter=HomeControllerTest` — 29 passed / 66 assertions.
- `php artisan test --filter=PageResourceTest --filter=PageObserverTest` — 4 passed.
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-11 — AR-тизер на главной странице (маркетинговый блок)

### Цель

Подготовить место на главной странице для будущей услуги дополненной реальности:
визуально привлекательное объявление «Скоро в Фотосказке — оживающие фотографии».
На этом этапе AR-функциональность, камеры, WebAR, JS-анимации и backend-логика
**не** реализуются — это исключительно статичный маркетинговый блок.

### Добавлено

- **resources/views/components/site/ar-teaser.blade.php** (новый) — отдельный
  Blade-компонент тизера:
  - бейдж «СКОРО» (золотой pill, иконка-magic);
  - заголовок `Скоро в Фотосказке — оживающие фотографии` (Forum, золотой акцент
    на второй строке);
  - короткое описание концепции + строка «Следите за новостями»;
  - media-блок со статичной фотографией, спроектированный под будущую замену
    на демо-видео/AR-демо без переделки структуры секции (`aspect`-контейнер,
    оверлей-градиент, общий контейнер);
  - декоративные золотые «линии-сканеры» (лёгкая CSS-анимация через `@keyframes`,
    отключена через `prefers-reduced-motion`), мягкое золотое свечение;
  - вся стилистика — существующая дизайн-система (тёмный фон `#0a0a0a/#111111`,
    золото `#d4af37`, Ubuntu/Forum, стандартные контейнеры `max-w-7xl`, `py-24`,
    адаптивные Tailwind-классы, AOS);
  - адаптивность: desktop / tablet / mobile; `loading="lazy"` + `decoding="async"`
    у изображения; осмысленный `alt`-текст.
- **public/images/ar-teaser.jpg** — лёгкий статичный плейсхолдер (34 КБ, тёмная
  гамма с золотыми акцентами); подлежит замене на реальное фото фотографа.

### Изменено

- **resources/views/home.blade.php** — `<x-site.ar-teaser />` вставлен строго
  между секциями «Избранные работы» и «Видеогалерея».
- **tests/Feature/Http/Controllers/HomeControllerTest.php** — добавлен тест
  `test_home_page_renders_ar_teaser` (главная отдаёт тизер: заголовок,
  классы, путь к изображению).

### Не реализовывалось

- CTA-кнопка, карточки преимуществ, интерактивность, JavaScript, AR.
- Изменения БД, моделей, контроллеров и маршрутов не вносились.

### Проверка

- `php artisan test --filter=HomeControllerTest` — 23 passed / 47 assertions.
- `php artisan test` — 878 passed / 2092 assertions; 9 падений в окружении
  (`touch(): Utime failed` из-за устаревшего кэша скомпилированных views с
  несовместимым владельцем) устраняются `php artisan view:clear`, в изоляции
  падавшие тесты проходят.
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-11 — Security: закрытие прямых `/storage/...` утечек приватной Media

### Цель

Аудит защиты выдачи media для клиентских галерей (цепочка
`Media → Photo → Album → authorization`, роуты `/media/{id}/*`, IDOR,
thumbnails и другие варианты URL). Требование: приватная Media не должна
получаться ни по какому URL без прав на соответствующую Photo/Album.

### Результат аудита

**Защищено (без изменений):** все роуты `/media/{id}/*` (`original`,
`download`, `display`, `lightbox`) гейтятся `MediaAccessService` через
`AlbumPolicy::view` (наследование Project → Album → Photo):
гость → 404, чужой → 403, свои client / назначенный parent / свой
class_manager / admin / photographer → 200. Покрыто
`MediaAccessAuthorizationTest`.

**Обнаруженные утечки (исправлены):** на страницах кабинета аксессоры
`Media::getUrl()` (локальные диски) и `Media::getThumbnailUrl()`
формировали **прямые** URL `…/storage/{file_path}` и
`…/storage/thumbnails/{thumbnail_path}`. Диск `thumbnails` и `public`-диск
оригиналов лежат внутри веб-корня (`public/storage → storage/app/public`),
поэтому эти файлы отдавались веб-сервером в обход шлюза авторизации —
приватные обложки/оригиналы клиентских альбомов были доступны по косту
прямой ссылки даже гостю.

### Исправление (минимальное, без переписывания Media Storage)

- **routes/web.php** — новый роут `GET /media/{media}/thumbnail`
  (`media.thumbnail`), за гейтом `MediaAccessService`.
- **app/Http/Controllers/MediaController.php** — метод `thumbnail()`:
  `authorizeView()` → стрим WebP-превью с диска `thumbnails`;
  отсутствие файла → 404.
- **app/Models/Media.php**:
  - `getUrl()` — всегда возвращает прокси-роут `media.original`
    (ранее для локальных дисков — прямую ссылку `/storage/...`);
  - `getThumbnailUrl()` — всегда возвращает прокси-роут `media.thumbnail`.
- Диск хранения, пути, миграции — **не изменялись**; политики
  (`AlbumPolicy`, `PhotoPolicy`, `MediaAccessService`) — не изменялись.

### Регрессионные тесты

- **tests/Feature/Http/Controllers/MediaAccessAuthorizationTest.php**:
  thumbnail приватной Media: гость → 404, чужой client → 403, свой
  client → 200; thumbnail публичной Media → гость 200; отсутствующий
  thumbnail → 404; свой class_manager видит media своего client-альбома;
  client не видит media чужого проекта (IDOR Project → Album → Media).
- **tests/Feature/Http/Controllers/Cabinet/CabinetAlbumShowTest.php**:
  страница приватной галереи не содержит прямых `/storage/...` ссылок
  (оригинал/превью), обложка альбома рендерится через `media.thumbnail`,
  а не `/storage/thumbnails/...`.
- **tests/Unit/Models/MediaModelTest.php**: `getUrl()`/`getThumbnailUrl()`
  больше не возвращают `/storage/...`; локальный диск → прокси-роут;
  thumbnail-роут при заданном `thumbnail_path`; null без `file_path`.

### Проверка

- `php artisan test` — **878 passed / 2201 assertions** (1 risky —
  предсуществующий, не связан с задачей).
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-11 — C2.6 — Галерея альбома в личном кабинете

### Цель

Реализовать страницу клиентской галереи альбома `/cabinet/albums/{album}`
с доступом через `AlbumPolicy::view` (защита от IDOR для всех ролей),
пагинацией при большом числе фото и выдачей media через существующую систему
Media Storage (`MediaController` / `ImageCacheService`).

### Добавлено

- **routes/web.php** — маршрут `GET /cabinet/albums/{album}` (middleware
  `auth`, имя `cabinet.album`). Не конфликтует с публичными `media.*` и
  `portfolio.*` маршрутами.
- **app/Http/Controllers/CabinetController.php** — метод `showAlbum()`:
  `Gate::authorize('view', $album)` (AlbumPolicy) → `CabinetService::paginateAlbumPhotos()`.
  Пользователь не может вытащить фото чужого альбома, подменив ID в URL — 403.
- **app/Services/CabinetService.php** — `getPhotosForAlbum()` переведён на
  пагинацию (`LengthAwarePaginator`, 24 фото/страница, `with('media')`);
  добавлен `paginateAlbumPhotos(Album, int $perPage)` для уже авторизованного
  альбома (без повторного запроса доступа и без N+1).
- **resources/views/cabinet/album.blade.php** (новый) — страница галереи:
  заголовок альбома, название проекта, описание, счётчик фото, сетка+lightbox
  через переиспользуемый `<x-site.album-photos>` (превью через
  `getDisplayUrl()`, оригинал через `getUrl()`/`getLightboxUrl()`), пагинация
  через стандартный Tailwind-пейджер, пустое состояние.
- **resources/views/components/site/album-photos.blade.php** — добавлен
  опциональный параметр `photos` (пагинированная коллекция) с сохранением
  прежнего поведения (`$album->photos` по умолчанию).
- **resources/views/cabinet/project.blade.php** — карточки альбомов теперь
  ссылаются на `cabinet.album` вместо «Открыть →»-заглушки `href="#"`.
- **resources/views/cabinet/index.blade.php** — карточки назначенных альбомов
  parent ведут на `cabinet.album`.

### Доступ (через AlbumPolicy, без изменений политики)

- client — альбомы собственных projects (любой тип);
- class_manager — только `type = client` альбомы собственного project;
- parent — только назначенные через `album_user` альбомы `type = client`;
- photographer/admin — полный доступ;
- Media выдаётся только через существующий Media Storage: для приватных
  типов альбомов (`client`, `project`) `MediaController` дополнительно
  проверяет `MediaAccessService::canView` (гость → 404, чужой → 403).
  Второе хранилище изображений не создаётся, фото в публичную директорию
  не копируются.

### Не реализовывалось

Комментарии (C2.8) и выбор фотографий — вне рамок задачи, согласно roadmap.

### Тесты

- **tests/Feature/Http/Controllers/Cabinet/CabinetAlbumShowTest.php** (новый, 27 тестов):
  - авторизация: гость → redirect на `/login`;
  - client: видит галерею своего альбома (любой тип), превью/lightbox/original
    URL через Media Storage; чужой альбом → 403; не видит медиа чужого альбома;
    пустое состояние; remote-медиа использует `media.original` route;
  - class_manager: `type = client` собственного проекта — доступно; `type =
    project` своего проекта и client чужих менеджеров → 403;
  - parent: назначенный `client`-альбом (в т.ч. без проекта) — доступно;
    неназначенный и назначенный не-client → 403;
  - photographer/admin: полный доступ;
  - без роли → 403; комбинированные роли (client+admin) — полный доступ;
  - IDOR: client A ↔ client B, manager A ↔ manager B, parent A ↔ parent B —
    взаимная изоляция;
  - пагинация: 60 фото → 2-я страница; 3 фото → пагинации нет;
  - навигация: назад на проект (client) / на кабинет (parent);
  - N+1: число SQL-запросов ограничено при сетке из 10 фото.

### Не менялось

- Policies (`AlbumPolicy`, `PhotoPolicy`) — без изменений; `PhotoPolicy::view`
  продолжает делегировать `AlbumPolicy`.
- Схема БД, миграции — без изменений.
- Media Storage / `MediaController` / `MediaAccessService` — без изменений.

### Проверка

- `php artisan test` — **867 passed / 2183 assertions** (1 risky —
  предсуществующий, не связан с задачей).
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-11 — C2.5 — Страница проекта в личном кабинете

### Цель

Реализовать страницу проекта `/cabinet/projects/{project}` внутри личного
кабинета с проверкой доступа через `ProjectPolicy::view` (защита от IDOR)
и роль-зависимым набором альбомов.

### Добавлено

- **routes/web.php** — маршрут `GET /cabinet/projects/{project}` (middleware
  `auth`, имя `cabinet.project`).
- **app/Http/Controllers/CabinetController.php** — метод `show()`:
  `Gate::authorize('view', $project)` → `CabinetService::getProjectForUser()`
  (IDOR-защита) → фильтрация альбомов: class_manager видит только
  `type = client`, прочие — все.
- **resources/views/cabinet/project.blade.php** (новый) — страница проекта:
  название, статус с бейджем, дата съёмки, описание, счётчик альбомов;
  сетка альбомов (обложка, название, описание, число фото, ссылка на будущую
  галерею «Открыть →»), пустое состояние; у project-albums cover тянутся
  через `albums.cover` (eager loading).
- **app/Services/CabinetService.php** — в `baseProjectQuery()` к загрузке
  `albums` добавлены `withCount('photos')` и `albums.cover` (без N+1).
- **resources/views/cabinet/projects.blade.php** — карточки проектов теперь
  ведут на `cabinet.project` вместо `href="#"`.

### Тесты

- **tests/Feature/Http/Controllers/Cabinet/CabinetProjectShowTest.php** (новый, 24 теста):
  - авторизация: гость → redirect на `/login`;
  - client: видит собственный проект (инфо, статус, описание, дата съёмки,
    счётчики, обложка, все альбомы любых типов); чужой проект → 403;
  - class_manager: видит собственный проект, только `type = client` альбомы;
    чужой проект → 403;
  - parent: доступ к проекту запрещён (403);
  - photographer/admin: полный доступ ко всем проектам и типам альбомов;
  - IDOR: client A ↔ client B, manager A ↔ manager B — взаимная изоляция;
    отсутствие чужих альбомов на странице проекта;
  - N+1: число SQL-запросов ограничено;
  - статус через `ProjectStatus::label()`; ссылка «Открыть →» для галереи;
  - список проектов ведёт на страницу проекта.

### Не менялось

- Policies (`ProjectPolicy`, `AlbumPolicy`, `PhotoPolicy`) — без изменений.
- Схема БД — без изменений.
- Содержимое галереи (C2.6) — не реализовывалось.

### Проверка

- `php artisan test` — **840 passed / 2132 assertions** (1 risky —
  предсуществующий, не связан с задачей).
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-11 — C2.4 — Список проектов

### Цель

Реализовать отдельную страницу списка проектов `/cabinet/projects` для client
и class_manager с проверкой доступа через `ProjectPolicy::view` (защита от IDOR).

### Добавлено

- **routes/web.php** — маршрут `GET /cabinet/projects` (middleware `auth`);
  обёрнут в `Route::middleware('auth')->group()` вместе с `GET /cabinet`.
- **app/Http/Controllers/CabinetController.php** — метод `projects()`:
  получает проекты через `CabinetService::getProjectsForUser()`, фильтрует
  через `$user->can('view', $project)` (ProjectPolicy::view).
- **resources/views/cabinet/projects.blade.php** (новый) — страница списка
  проектов: заголовок (роль-зависимый), обратная ссылка на dashboard, карточки
  проектов (название, статус с бейджем, дата съёмки, счётчики альбомов/фото),
  пустое состояние.
- **resources/views/cabinet/index.blade.php** — добавлена ссылка «Все проекты»
  на `cabinet.projects` в заголовке для client/class_manager/admin/photographer.
  Для parent ссылка не показывается.

### Тесты

- **tests/Feature/Http/Controllers/Cabinet/CabinetProjectsTest.php** (новый, 26 тестов):
  - авторизация: гость → redirect на `/login`; авторизованный — `200`;
  - client: видит свои проекты и статус, не видит чужих; счётчики альбомов/фото;
    заголовок «Мои проекты»; пустое состояние;
  - class_manager: видит свой проект и статус, не видит чужих; счётчик только
    `client`-альбомов; заголовок «Ваш проект»; пустое состояние;
  - parent: не видит проекты; пустое состояние;
  - photographer/admin: видят все проекты;
  - IDOR: client A ↔ client B, manager A ↔ manager B, parent A ↔ parent B —
    взаимная изоляция;
  - N+1: число SQL-запросов ограничено и не растёт с количеством вложенных
    сущностей;
  - бейдж статуса: русское название через `ProjectStatus::label()`;
  - ссылка с dashboard на projects: видна для client/class_manager, не видна
    для parent;
  - проекты проходят проверку `ProjectPolicy::view`;
  - комбинированные роли: client+admin.

### Не менялось

- Policies (`ProjectPolicy`, `AlbumPolicy`, `PhotoPolicy`) — без изменений.
- Слой данных `CabinetService` (C2.2) — без изменений.
- Схема БД — без изменений.

### Проверка

- `php artisan test` — **816 passed / 2074 assertions** (1 risky —
  предсуществующий, не связан с задачей).
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-10 — C2.3 — Dashboard личного кабинета

### Цель

Реализовать главную страницу личного кабинета `/cabinet` с роль-ориентированным
содержимым для `client`, `class_manager`, `parent` и сохранением корректного
поведения для `photographer`/`admin` на базе существующего слоя данных C2.2
и Policies C1.

### Изменено

- **resources/views/cabinet/index.blade.php** — dashboard доведён до вида C2.3
  в стилистике публичного сайта (тёмная тема `#0a0a0a`/`#111111`, `font-heading`,
  золотые акценты):
  - `client` — «Ваши проекты»: карточки собственных проектов с русским статусом
    (бейдж с цветовой картой по `ProjectStatus::value`), датой съёмки,
    `client_albums_count`, `photos_count` и первыми альбомами;
  - `class_manager` — «Ваш проект»: собственный проект и статус; внутри карточки
    показываются только `client`-альбомы проекта (`->where('type', 'client')`
    на уже выбранной коллекции, без повторных запросов);
  - `parent` — «Назначенные альбомы»: только назначенные `client`-альбомы
    (обложка или плейсхолдер, название, проект, описание); проекты не видны;
  - `photographer`/`admin` — «Проекты»: все проекты платформы;
  - пустые состояния роль-зависимы: parent — «Нет назначенных альбомов»
    + пояснение «когда фотограф назначит их вам»; остальные — «Нет доступных
    проектов»;
  - альбомы без обложки получают иконку-плейсхолдер вместо пустого блока.
- **app/Models/Project.php** — добавлен каст `shooting_date` → `date`.
  На этапе C2.2 шаблон вызывал `$project->shooting_date->format('d.m.Y')`, но
  колонка `DATE` не кастовалась: на SQLite (тесты) значение приходит строкой и
  вызов `->format()` падал. Каст устраняет latent-баг; значение в БД не меняется.

### Тесты

- **tests/Feature/Http/Controllers/CabinetControllerTest.php** (новый, 31 тест):
  - доступ: гость → redirect на `/login`; авторизованный — `200` + имя в приветствии;
  - `client`: видит свои проекты и статус («Обработка фотографий»), не видит чужих;
    счётчики альбомов/фото; заголовок «Ваши проекты»; пустое состояние;
  - `class_manager`: видит свой проект и статус («Фотосъёмка закончена»), не видит
    чужих; видит только `client`-альбомы (даже при наличии `project`-альбома);
    заголовок «Ваш проект»; пустое состояние; счётчик только `client`-альбомов;
  - `parent`: видит назначенные альбомы и заголовок «Назначенные альбомы»; не видит
    проекты, чужие альбомы и `project`-альбомы даже при `album_user`; пустое состояние;
    счётчик альбомов;
  - `photographer`/`admin`: видят все проекты; заголовок «Проекты»; пустое состояние;
  - отсутствие чужих данных: client A ↔ client B, manager A ↔ manager B,
    parent A ↔ parent B — взаимная изоляция;
  - N+1: число SQL-запросов на страницу (client/проекты и parent/альбомы) ограничено
    и не растёт с количеством вложенных сущностей (eager loading в `CabinetService`).
- **tests/Feature/InquiryTest.php** — `test_create_project_in_transaction` переведён
  с сырого сравнения `shooting_date` в БД (зависело от формата хранения СУБД) на
  проверку через каст: `$project->shooting_date instanceof \DateTimeInterface`
  + `format('Y-m-d') === '2026-09-15'`. Намерение теста (транзакционность) сохранено.
- **tests/Feature/Services/CabinetServiceTest.php** — у теста
  `test_projects_eager_load_albums_and_photos_count` устранена флаки-зависимость:
  `albumA` получал случайный `type` из `AlbumFactory` (мог оказаться `client`,
  ломая ожидание `client_albums_count = 1`); тип зафиксирован как `project`.

### Не менялось

- Policies (`ProjectPolicy`, `AlbumPolicy`, `PhotoPolicy`) — единственный источник
  правил авторизации; роль в шаблоне используется только для формулировок UI.
- Слой данных `CabinetService` (C2.2) — без изменений.
- Схема БД — без изменений (миграции не добавлялись).
- Полноценная страница проекта и галерея не реализовывались (рамки C2.3).

### Проверка

- `php artisan test` — **790 passed / 2011 assertions** (1 risky —
  предсуществующий `test_three_level_category_page_renders_full_breadcrumb`,
  не связан с задачей).
- `./vendor/bin/pint --test` — чисто.

---

# Changelog

## 2026-09-10 — C2.2 — Слой данных личного кабинета

### Цель

Создать слой получения данных для личного кабинета (`CabinetService`),
на который лягут последующие подэтапы C2.3–C2.6 (dashboard, список проектов,
страница проекта, галерея альбома). `CabinetController` остаётся тонким,
без ветвлений по ролям внутри контроллера.

### Добавлено

- **app/Services/CabinetService.php** (новый) — единственная точка выборки данных
  кабинета, с роль-ориентированными запросами и eager loading без N+1:
  - `getProjectsForUser(User)` — проекты для dashboard/списка: клиент — свои
    (`projects.client_id`), class_manager — свой (`projects.manager_id`),
    admin/photographer — все, parent и пользователи без роли — пусто.
  - `getProjectForUser(User, int $projectId)` — проект по ID с теми же фильтрами
    (IDOR-защита: чужой/недоступный проект → null).
  - `getAlbumsForUser(User)` — альбомы для dashboard родителя: admin/photographer —
    все `client`-альбомы; client — альбомы своих проектов; class_manager —
    `client`-альбомы своего проекта; parent — только назначенные через `album_user`
    `client`-альбомы; без роли — пусто.
  - `getAlbumForUser(User, int $albumId)` — альбом по ID с теми же фильтрами.
  - `getPhotosForAlbum(User, int $albumId)` — фото альбома (с `media`) только после
    подтверждения доступа к альбому.
  - `Project`-запросы дополняются `withCount`: `albums_count`,
    `client_albums_count`, `photos_count` (подзапросом, без N+1).
  - Eager loading: `project`, `cover`, `users` для альбомов; `albums` с сортировкой
    для проектов; `media` для фото.
  - Критичное правило фильтрации закрытия: если у пользователя нет ни одной
    применимой роли — запрос получает `where 1 = 0` (пустая выборка), а не
    «без ограничений».

### Изменено

- **app/Http/Controllers/CabinetController.php** — внедрён `CabinetService` (DI);
  `index()` отдаёт данные в зависимости от роли: parent → `albums`, остальные →
  `projects`. Логика выборки переехала из контроллера в сервис.
- **resources/views/cabinet/index.blade.php** — заглушка заменена на рендер
  dashboard: для parent — карточки назначенных альбомов (обложка, название,
  проект, описание); для клиента/class_manager/admin — карточки проектов
  (название, статус, дата съёмки, счётчики альбомов и фото, список альбомов).
  UI галереи намеренно не создавался (рамки C2.2).

### Не менялось

- Policies (`ProjectPolicy`, `AlbumPolicy`, `PhotoPolicy`) остаются единственным
  источником правил авторизации; query layer не дублирует бизнес-логику Policy.
- Схема БД не изменялась.

### Тесты

- **tests/Feature/Services/CabinetServiceTest.php** (новый, 30 тестов):
  - admin/photographer — полный доступ (все проекты, проект по ID, фото альбома);
  - client — только свои проекты, все типы альбомов своих проектов, отсутствие
    чужих и orphan-альбомов; IDOR: чужой проект/альбом → null;
  - class_manager — только свой проект и его `client`-альбомы; чужие проекты,
    альбомы типа `project`/прочие — недоступны; IDOR → null;
  - parent — проектов не видит, только назначенные `client`-альбомы (в т.ч. без
    проекта), не-`client` альбомы недоступны даже при `album_user`; IDOR → null;
  - пользователь без роли — пустая выборка;
  - комбинированные роли: client+class_manager, client+admin;
  - eager loading: сортировка альбомов проекта, связи `project`/`cover`/`users`,
    счётчики `albums_count`/`client_albums_count`/`photos_count`.

### Проверка

- `php artisan test` — **759 passed / 1932 assertions** (1 risky —
  предсуществующий, не связан с задачей).
- `./vendor/bin/pint --test` — чисто.

---

# Changelog

## 2026-09-09 — C2.1 — Финализация статусов проекта

### Цель

Создать единое представление допустимых бизнес-статусов `Project` перед разработкой
личного кабинета (подэтап C2.1 roadmap), устранить устаревшее значение `active`
и перевести админку на русские названия статусов.

### Добавлено

- **app/Enums/ProjectStatus.php** (новый) — единый источник допустимых статусов
  (BackedEnum): `draft`, `shooting_completed`, `reshoot`, `processing`,
  `layout_approval`, `printing`, `completed`, `archived`.
  - `label()` — русское название (Подготовка, Фотосъёмка закончена, Пересъёмка,
    Обработка фотографий, Согласование макета, Отправка в печать, Проект завершён,
    Архив);
  - `color()` — цвет бейджа Filament;
  - `options()` — единый список `[value => label]` для формы/таблицы/фильтра.

### Изменено

- **app/Models/Project.php** — `status` в `$casts` → `ProjectStatus::class`;
  заполняемые поля не менялись.
- **database/factories/ProjectFactory.php** — статус генерируется из
  `ProjectStatus::cases()` (убрано устаревшее `active`).
- **app/Actions/Inquiry/CreateProjectFromInquiry.php** — статус по умолчанию
  `ProjectStatus::Draft` вместо строки `'draft'`.
- **app/Filament/Resources/Projects/Schemas/ProjectForm.php** — Select статуса
  переведён на `ProjectStatus::options()`; дефолт `ProjectStatus::Draft->value`.
- **app/Filament/Resources/Projects/Tables/ProjectsTable.php** — бейдж статуса:
  русское название через `label()`, цвет через `color()`; фильтр статуса — на
  `ProjectStatus::options()`.
- **app/Filament/Resources/Inquiries/Schemas/InquiryForm.php** — отображение
  `project.status` переведено на русское название через `label()`.

### Миграция

- **database/migrations/2026_09_09_072522_update_projects_status_enum_table.php**
  (новая):
  - расширяет `ENUM('projects.status')` до 8 значений; старое `active` убрано;
  - существующие проекты `status = 'active'` переводятся в `processing`
    (ближайший этап активной работы; в фактических данных таких записей нет —
    конверсия выполнена безопасно);
  - `down()` восстанавливает старый enum (`draft/active/completed/archived`),
    переводя новые статусы обратно в `active`;
  - миграция работает и на MySQL (`ALTER TABLE ... MODIFY`), и на SQLite
    (пересборка таблицы с заменой `CHECK`-ограничения и конверсией данных
    на лету), чтобы тестовый набор на SQLite оставался зелёным.

### Прочее

- Полноценный workflow переходов между статусами не внедрялся (вне рамок C2.1);
  `reshoot` не является строго линейным следующим состоянием — после пересъёмки
  проект может вернуться к предыдущему этапу.
- Личный кабинет, галереи и комментарии не затрагивались.

### Документация

- **database.md**: таблица `projects` — новый enum `status` + таблица допустимых
  значений и примечание о `reshoot` и устранении `active`.
- **architecture.md**: новый подраздел «Статусы проекта (C2.1)» в разделе ролей/доступа.

### Тесты

- **tests/Unit/Enums/ProjectStatusTest.php** (новый, 12 тестов) — состав `cases()`,
  отсутствие `active`, русские названия, `options()`, цвета.
- **tests/Feature/Models/ProjectModelTest.php** (новый, 4 теста) — каст в enum,
  дефолт `draft`, строка в БД, поддержка всех 8 статусов.
- **tests/Feature/InquiryTest.php** — проверка статуса при создании проекта из
  заявки переведена на `ProjectStatus::Draft`.

### Проверка

- `php artisan test` — **729 passed / 1877 assertions** (1 risky — предсуществующий
  `test_three_level_category_page_renders_full_breadcrumb`, не связан с задачей)
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-09 — C1.6 — Финальный security-аудит доступа

### Цель

Финальная проверка модели доступа C1 (без добавления новой функциональности).
Проверена фактическая матрица доступа против ожидаемой:

```text
client          → свои Project → все Albums → все Photos
parent          → назначенный Album → Photos этого Album
class_manager   → свой Project → Albums type=client → Photos
photographer    → все
admin           → все
```

### Аудит

- **Project access** — `ProjectPolicy::view` реализует матрицу ролей
  (admin/photographer → все; client → `client_id`; class_manager → `manager_id`;
  parent → нет). Покрыто `ProjectPolicyTest` (свой/чужой проект, проект без
  client, проект без manager, комбинации ролей). **PASS**
- **Album access** — `AlbumPolicy::view` (admin/photographer → все; client →
  `project.client_id`; class_manager → `client`-альбомы своего проекта; parent →
  назначенный через `album_user` `client`-альбом). Покрыто `AlbumPolicyTest`
  (свой/чужой client-album, project album, album без project, parent без
  `album_user`, parent с несколькими `album_user`, client проекта, manager
  другого проекта). **PASS**
- **Photo access** — `PhotoPolicy::view` делегирует в `AlbumPolicy`
  (наследование Project → Album → Photo). Покрыто `PhotoPolicyTest`. **PASS**
- **IDOR** — отдельные HTTP-эндпоинты `/client/projects/{id}` и
  `/client/albums/{id}` и `/media/photo/{id}` **не существуют**
  (`CabinetController` — заглушка этапа 5). Единственная точка прямого доступа
  по ID — роуты `MediaController` (`/media/{id}/original|download|display|lightbox`),
  защищены шлюзом `MediaAccessService` (C1.5): приватная Media → гость `404`,
  чужой пользователь `403`. Покрыто `MediaAccessAuthorizationTest`. **PASS**
- **HTTP endpoints** — публичные контроллеры (`PortfolioController`,
  `HomeController`, `ServiceCatalogController`, `BlogController`) отдают только
  опубликованный публичный контент (`type = portfolio`/публичные), приватные
  альбомы/фото не отдаются. **PASS**
- **Mass queries** — `Model::all()` в приложении отсутствует; выборки
  фильтруются на уровне Query Builder (`where('type', ...)`,
  `where('is_published', true)`). Клиентских массовых выборок пока нет
  (кабинет не реализован — этап 5 roadmap). **PASS**
- **Отсутствие утечек** — `CabinetController` возвращает только `$user->name`;
  названия/ссылки/URL/счётчики чужих проектов и альбомов в ответы не попадают.
  **PASS**

### Итог

Проблем в модели доступа C1 не обнаружено. Исправления и regression-тесты
не требовались (дефекты C1.2–C1.5 уже исправлены в предыдущих шагах;
покрытие тестами присутствует). Документация (`architecture.md` разделы C1.2–C1.5,
`database.md` `album_user`) уже соответствует фактической модели доступа,
roadmap не менялся (перенос на отдельный этап после завершения всей C1).

### Проверка

- `php artisan test` — **713 passed / 1843 assertions** (1 risky —
  предсуществующий `test_three_level_category_page_renders_full_breadcrumb`,
  не связан с задачей)
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-09 — C1.5 — Подключение Policies к реальным точкам доступа

### Аудит точек доступа

Найденные места, где пользователь может получить `Project` / `Album` / `Photo`:

| Точка доступа | Тип | Статус |
|---------------|-----|--------|
| `CabinetController@index` (`/cabinet`) | заглушка, возвращает только `$user->name` | приватных данных не отдаёт; защищён middleware `auth` |
| `PortfolioController` | только `type = portfolio` + `is_published` | публичный контент, объекты/`Photo` приватных альбомов не отдаются |
| `ServiceCatalogController`, `HomeController` | только опубликованный публичный контент | приватные данные не отдаются |
| **`MediaController`** (`/media/{media}/original`, `/download`, `/display`, `/lightbox`) | **универсальный, по `Media` ID** | **УЯЗВИМ (IDOR)** — отдаёт файл любого `Media` без проверки владельца, в обход `AlbumPolicy`/`PhotoPolicy` |

Отдельных HTTP/AJAX-эндпоинтов для `Project`/`Album`/`Photo` в текущем коде нет —
коль скоро `CabinetController` пока заглушка (этап 5 roadmap). Единственная точка,
через которую сейчас можно получить приватный контент напрямую по ID, — роуты `Media`.

### Проблема Media (зафиксирована)

`Media` универсален: одна и та же запись переиспользуется публичными альбомами
(портфолио, услуги, homepage, обложки, отзывы) и приватными (клиентские/проектные
галереи). `Media` не знает «владельца контента», поэтому `AlbumPolicy`/`PhotoPolicy`
к нему напрямую не применимы. Полностью переписывать Media Storage в рамках C1.5
запрещено заданием и не требуется.

### Минимальное архитектурное решение

Не переписывая хранение, добавлен **входной шлюз авторизации** поверх существующих
роутов `Media` (`app/Services/MediaAccessService.php`):

- у `Media` определена связь `media → photos → albums` (pivot `photos`);
- `Media` считается **приватной**, если хотя бы один альбом, ссылающийся на неё
  через `Photo`, имеет `type` = `client` или `project`;
- публичная Media отдаётся как раньше (гость видит её);
- приватная Media: гость → `404` (не раскрываем существование); авторизованный
  пользователь без доступа → `403`; доступ проверяется через `AlbumPolicy::view`
  (делегирование `PhotoPolicy` соблюдается) для хотя бы одного содержащего приватного
  альбома (`admin`/`photographer` — полный доступ; `client` — свой проект;
  `class_manager` — `client`-альбом своего проекта; `parent` — назначенный `client`-альбом).

Тем самым приватная фотография **не** отдаётся через отдельный URL `Media` в обход
`Photo`/`Album` авторизации. Корневая причина (универсальность `Media`, переиспользование
одного `Media` публичным и приватным альбомом) — документирована; полноценное решение
будет рассмотрено на этапе 5 (клиентские галереи), когда появится контекст родительского
альбома/проекта.

### Изменено

- **app/Models/Media.php** — добавлены связи `photos()` (HasMany) и
  `albums()` (BelongsToMany через pivot `photos`).
- **app/Services/MediaAccessService.php** (новый) — решение о публичности/доступе
  к `Media` на основе его приватных альбомов.
- **app/Http/Controllers/MediaController.php** — все 4 метода
  (`original`, `download`, `display`, `lightbox`) вызывают `authorizeView()`
  до отдачи файла.

### Какие Policy реально используются

- `AlbumPolicy::view` (через `MediaAccessService::canView` + `User::can`) — фактический
  шлюз для доступа к файлам приватных альбомов.
- `PhotoPolicy::view` — делегируется в `AlbumPolicy` (правило наследования
  `Project → Album → Photo` соблюдается автоматически: доступ к фото определяется
  доступом к альбому).
- `ProjectPolicy::view` — на текущем этапе прямых HTTP-точек нет; подключится при
  появлении эндпоинтов этапа 5 (документировано).

### Feature-тесты (`tests/Feature/Http/Controllers/MediaAccessAuthorizationTest.php`)

Реальные HTTP-запросы в матрицу доступа к фото посредством роутов `Media`:

- публичная Media → гость `200`;
- приватная Media → гость `404`;
- свой client → своя приватная Media `200`;
- чужой client → чужая приватная Media `403`;
- чужой parent → чужая приватная Media `403`;
- назначенный parent → своя приватная Media `200`;
- чужой class_manager → чужая приватная Media `403`;
- admin → любая приватная Media `200`;
- display/lightbox приватной Media: гость `404`, чужой пользователь `403`;
- display публичной Media → гость `200`.

### Результаты

- `php artisan test` — **713 passed / 1843 assertions** (1 risky — существующий).
- `./vendor/bin/pint --test` — чисто.

---

## 2026-09-09 — Исправление авторизации admin в Filament (CRUD-методы Policy + админ в тестах)

### Причина
После внедрения `AlbumPolicy`, `ProjectPolicy` и `PhotoPolicy` (C1.2–C1.4) в policy
были только методы `view`/`viewAny`. Laravel Gate при отсутствии нужного метода
в policy возвращает `false` (политика полностью пропускается, `before` не вызывается),
поэтому страницы Filament (`Edit/Create/Delete`) для `Album`, `Project`, `Photo`
отдавали **403** даже администратору. Валился тест
`test_deleting_album_keeps_media` («Attempt to read property "mountedActions" on null»).

Дополнительно: тесты не сигнализировали подлинную причину — их `setUp()` перекрывал
`setUp()` трейта `AdminTestCase`, поэтому администратор никогда не создавался и не
аутентифицировался (Livewire-компоненты монтировались анонимно).

### Изменено
- **app/Policies/AlbumPolicy.php** — добавлены CRUD-методы `create`, `update`,
  `delete` (право управления: роли `admin` и `photographer`).
- **app/Policies/ProjectPolicy.php** — добавлены `viewAny`, `create`, `update`,
  `delete` (те же роли).
- **app/Policies/PhotoPolicy.php** — добавлены `viewAny`, `create`, `update`,
  `delete`; CRUD делегируется в `AlbumPolicy` через альбом фотографии
  (`create` принимает необязательный `Album`).
- **tests/Feature/Filament/AdminTestCase.php** — логика администратора вынесена
  в метод `signInAsAdmin()`; `setUp()` трейта вызывает его, что позволяет
  переопределяющим `setUp()` тестам восстанавливать аутентифицированного админа.
- Тесты с собственным `setUp()`, перекрывающим трейт, теперь вызывают
  `$this->signInAsAdmin()`:
  `MediaReuseSafetyTest`, `AlbumPhotosRelationManagerTest`, `MediaDeletionTest`,
  `MediaUploadTest`, `MediaRetryProcessingTest`.

### Тесты
- 702 tests — passed (было 701 passed / 1 error).

## 2026-09-07 — Админка: добавление существующего медиа из другого альбома

### Добавлено
- **app/Filament/Resources/Albums/RelationManagers/PhotosRelationManager.php** —
  новое header-действие **«Добавить из альбома»** (иконка `heroicon-o-plus-circle`):
  - выбор **альбома-источника** через `Select` с поиском (текущий альбом
    исключён из списка);
  - выбор **фотографий** через `CheckboxList` (3 колонки), наполняемый
    динамически после выбора альбома-источника (live/reactive);
  - **дедупликация**: из выбора исключаются медиа, уже присутствующие
    в текущем альбоме (сравнение по `media_id`);
  - создание записей `Photo` для выбранных медиа с
    `sort_order` = `max(sort_order) + 1..n` (добавление в конец);
  - уведомление об успехе/пустом выборе;
  - повторное использование одного и того же `Media` в нескольких альбомах
    через несколько строк `photos` (структура БД не менялась).

### Изменено
- В структуру БД изменения не вносились — переиспользование реализовано
  средствами существующей схемы (`albums → photos → media`).

### Тесты
- **tests/Feature/Filament/AlbumPhotosRelationManagerTest.php** — добавлены
  тесты на новое действие:
  - `test_add_from_album_action_is_available`;
  - `test_add_from_album_creates_photos_from_source_album` (добавление новой
    фотографии с `sort_order` после максимума);
  - `test_add_from_album_action_with_empty_media_selection_does_not_add_photos`;
  - `test_add_from_album_action_skips_media_already_in_target_album` (дедупликация).

## 2026-09-07 — C1.4: PhotoPolicy — доступ к фотографиям через AlbumPolicy

### Добавлено
- **app/Policies/PhotoPolicy.php** — единственный источник решения «может ли
  пользователь просматривать Photo»:
  - `view(User $user, Photo $photo): bool` — единственный метод (без избыточных
    `viewAny` и прочих — не используются существующей функциональностью;
    кабинет/контроллеры/UI не реализуются в рамках задачи);
  - правило полностью делегировано `AlbumPolicy`: `$user->can('view', $photo->album)`;
  - **отсутствует дублирование матрицы ролей** — отдельные правила
    `client → photo`, `parent → photo`, `class_manager → photo` не создавались.
    Иерархия доступа: `Project → Album → Photo`;
  - N+1: PhotoPolicy не выполняет собственных запросов — решение целиком
    в `AlbumPolicy`. Массовые проверки требуют корректного eager loading
    связей `photo->album` (+ `album->project` для client/class_manager)
    со стороны вызывающего кода (задокументировано в `AlbumPolicy`);
  - Policy подключена стандартным автодискавери Laravel
    (`App\Policies\{Model}Policy`).

### Тесты
- **tests/Feature/Policies/PhotoPolicyTest.php** (17 тестов, 49 утверждений) —
  полная матрица доступа и особые случаи:
  - `admin`/`photographer` → любое фото (включая `project`/`portfolio`/orphan-альбомы);
  - `client` A/B — только фото своих проектов, любых типов альбомов; без проекта — нет;
  - `class_manager` A/B — только фото `client`-альбомов своего проекта
    (проект- и portfolio-типы и чужие проекты запрещены); без проекта — нет;
  - `parent` — только фото назначенного `client`-альбома (другой клиентский,
    project/portfolio/homepage/service даже при назначении — нет);
  - пользователь без роли, гость — нет;
  - комбинирование ролей: `client`+`class_manager`, `client`+`admin`;
  - **IDOR-тест**: User A / Photo A→Album A и User B / Photo B→Album B —
    User A не получает Photo B даже при знании её ID (и симметрично для User B);
  - авто-дискавери Policy.

### Не реализовано (границы задачи)
Загрузка/удаление фотографий, комментарии, UI, клиентский кабинет, контроллеры
и маршруты — вне рамок C1.4. Публичные страницы не изменялись.

### Документация
- **architecture.md**: раздел «Система ролей и доступа» дополнен подразделом
  «Policy для фотографий (C1.4)» — делегирование в `AlbumPolicy`, отсутствие
  дублирования матрицы ролей, подход к N+1.

### Проверка
- **tests/Feature/Policies/PhotoPolicyTest.php**: 17 tests, 49 assertions — passed
- Полный набор: 697/698 passed (1 предсуществующая ошибка
  `MediaReuseSafetyTest::test_deleting_album_keeps_media` — Livewire
  "mountedActions on null", не связана с задачей; см. C1.3)
- Pint: clean для новых файлов (app/Policies, tests/Feature/Policies)

## 2026-09-07 — C1.3: AlbumPolicy — разграничение доступа к альбомам

### Добавлено
- **app/Policies/AlbumPolicy.php** — доменное правило доступа к `Album` (единственный
  источник решения «может ли пользователь просматривать Album»):
  - `viewAny(User $user): bool` — позволяет только `admin`/`photographer`;
    не используется как источник бизнес-правил (без контекста проекта корректно
    не реализуемо для client/class_manager/parent);
  - `view(User $user, Album $album): bool` — главное правило:
    - `admin` и `photographer` — полный доступ ко всем альбомам (приоритет ролей);
    - `client` — только к альбомам проектов, которыми владеет:
      `album.project.client_id === user->id` (pivot `client → album` не создавался,
      источник права — `User → Project.client_id → Album.project_id`), тип альбома
      не ограничен;
    - `class_manager` — только к `client`-альбомам своего проекта:
      `album.type === 'client'` И `album.project.manager_id === user->id`;
      на `project`/`portfolio`/прочие типы и чужие проекты доступ не распространяется;
    - `parent` — только к назначенному альбому через `album_user`
      И `album.type === 'client'`; остальные альбомы проекта, `project`/`portfolio`
      типы и альбомы без связи недоступны;
    - пользователь без роли и гость — доступа нет;
    - при нескольких ролях правила комбинируются предсказуемо: `admin`/`photographer`
      доминируют; для остальных доступ разрешён, если совпадает хотя бы одно из
      применимых правил (`client` ИЛИ `class_manager` ИЛИ `parent`) — без
      преждевременного `return` в ветках;
    - крайние случаи: альбом без `project` недоступен `client`/`class_manager`,
      но доступен `parent` при связи `album_user` и типе `client`;
    - Policy подключена стандартным автодискавери Laravel (`App\Policies\{Model}Policy`).
  - N+1: для проверки `client`/`class_manager` используется связь `album->project`
    (в массовых проверках вызывающий код подготавливает eager loading `project`);
    для `parent` — точечный запрос через существующую связь `album->users()`
    (`exists()`), без загрузки коллекции.

### Тесты
- **tests/Feature/Policies/AlbumPolicyTest.php** (18 тестов, 48 утверждений) — матрица
  доступа и особые случаи: admin/photographer → любой альбом; client A/B — только проекты
  A/B (pivot нет); class_manager A/B — только `client`-альбомы своего проекта
  (проект- и portfolio-типы запрещены); parent — только назначенный `client`-альбом
  (другой клиентский, project/portfolio/homepage/service даже при назначении — нет);
  альбом без `project`; client/class_manager без проекта; parent без `album_user`;
  пользователь без роли; гость; client + class_manager; client + admin;
  `viewAny` для ролей; авто-дискавери Policy.

### Не реализовано (границы задачи)
PhotoPolicy, кабинет, контроллеры, маршруты, комментарии, статусы, UI — вне рамок C1.3.
Публичные страницы не изменялись.

### Документация
- **architecture.md**: раздел «Система ролей и доступа» дополнен подразделом
  «Policy для альбомов (C1.3)» с правилами `view`/`viewAny`, крайними случаями,
  комбинированием ролей и подходом к N+1.
- **database.md**: в раздел `album_user` добавлена ссылка на `AlbumPolicy`
  (требование `type = 'client'` для `parent`/`class_manager`); схема не менялась.
- **roadmap.md**: в «Текущий статус» добавлен пункт «C1 — разграничение доступа
  к проектам и альбомам (C1.1–C1.3)», уточнено описание текущего этапа 5.

### Проверка
- Полный тестовый набор: 640 тестов, 639 passed + 1 предсуществующая ошибка
  `MediaReuseSafetyTest::test_deleting_album_keeps_media` (Livewire "mountedActions on null",
  не связана с задачей; Filament AlbumResourceTest — 10/10 passed);
  18 новых тестов / 48 утверждений — passed
- Pint: clean (app/Policies, tests/Feature/Policies)

## 2026-09-07 — C1.2: ProjectPolicy — правила доступа к проектам

### Добавлено
- **app/Policies/ProjectPolicy.php** — доменное правило доступа к `Project` (единственный
  источник решения «может ли пользователь просматривать Project»):
  - `view(User $user, Project $project): bool` — единственный метод (без избыточных
    `viewAny` и прочих — кабинет/UI/контроллеры не реализуются в рамках задачи);
  - `admin` и `photographer` — полный доступ ко всем проектам (приоритет ролей);
  - `client` — доступ только при `project.client_id === user->id`;
  - `class_manager` — доступ только при `project.manager_id === user->id`;
  - `parent` — доступа к Project не получает, даже если в проекте есть альбом,
    назначенный этому родителю (родитель видит только свой альбом);
  - пользователь без роли и гость — доступа нет;
  - при нескольких ролях правила комбинируются предсказуемо: `admin`/`photographer`
    доминируют, для остальных ролей доступ разрешён, если совпадает хотя бы одно
    из применимых правил (`client` ИЛИ `class_manager`);
  - Policy подключена стандартным автодискавери Laravel (`App\Policies\{Model}Policy`),
    новая система ACL не создавалась, используется существующая система ролей.

### Тесты
- **tests/Feature/Policies/ProjectPolicyTest.php** (11 тестов, 21 утверждение) — матрица
  доступа и особые случаи: admin/photographer → любые проекты; client A/B — только свои;
  class_manager A/B — только свои; parent — никакой проект, даже при назначенном альбоме;
  гость; пользователь без роли; client + class_manager (обе связи); client + admin;
  авто-дискавери Policy; client против проекта без клиента.

### Не реализовано (границы задачи)
AlbumPolicy, PhotoPolicy, кабинет, контроллеры, комментарии, статусы — вне рамок C1.2.

### Документация
- **architecture.md**: раздел «Система ролей и доступа» дополнен описанием `ProjectPolicy`.

### Проверка
- Полный тестовый набор: 622/622 passed (611 до + 11 новых), 1660 утверждений;
  единственный risky — предсуществующий `ServiceCatalogControllerTest::test_three_level_category_page_renders_full_breadcrumb` (не связан с задачей)
- Pint: clean (app/Policies, tests/Feature/Policies)

## 2026-09-04 — Кнопка CTA на страницах услуг и категорий

### Добавлено
- **Миграция `add_cta_button_fields_to_services_and_categories_table`** — добавлены
  в `services` и `categories`: `cta_album_id` (nullable FK → `albums`, ON DELETE SET NULL)
  и `cta_button_text` (nullable VARCHAR(255)).
- **app/Models/Service.php** — связь `ctaAlbum()` (BelongsTo → Album); `cta_album_id`,
  `cta_button_text` в `$fillable`.
- **app/Models/Category.php** — связь `ctaAlbum()` (BelongsTo → Album); `cta_album_id`,
  `cta_button_text` в `$fillable`.
- **ServiceForm** (Filament) — секция «Кнопка CTA»: Select альбома и TextInput текста кнопки.
- **CategoryForm** (Filament) — аналогичная секция «Кнопка CTA».
- **ServiceCatalogController** — eager loading `ctaAlbum` (опубликованный) на страницах
  услуги и категории.
- **resources/views/services/show.blade.php** — золотая кнопка перед формой заявки
  (при заполненных `cta_album_id` + `cta_button_text`).
- **resources/views/services/category.blade.php** — аналогичная кнопка перед формой заявки.

### Документация
- Обновлён `database.md` (поля, FK, индексы, описание CTA-кнопки для categories и services).
- Обновлён `changelog.md`.

---

## 2026-09-03 — Исправление устаревшего теста обложки категории

### Исправлено
- **tests/Feature/Http/Controllers/ServiceCatalogControllerTest.php** —
  `test_category_page_shows_cover_image` проверял оригинальный путь
  `covers/album-cover.jpg`, тогда как страница категории рендерит обложку через
  display-кэш (`getDisplayUrl()` → `/media/{id}/display`). Тест переведён на
  современный паттерн (как в `PortfolioControllerTest`): проверяется
  `route('media.display', ...)` + `alt`.

### Проверка
- Полный тестовый набор: 600/600 passed (ранее падал 1 тест)

---

## 2026-09-02 — Прикрепление альбомов к категориям

Дублированы возможности услуг для категорий каталога (`type = service`): прикрепление
альбомов-примеров и вывод выбранного альбома блоком с фото.

### Добавлено
- **Миграция** `2026_09_02_175143_create_category_album_table` — pivot `category_album`
  (many-to-many категории ↔ альбомы, `CASCADE` с обеих сторон).
- **Миграция** `2026_09_02_175149_add_featured_album_fields_to_categories_table` —
  в `categories` добавлены `show_album_photos` (boolean, default false)
  и `featured_album_id` (nullable FK → `albums`, `ON DELETE SET NULL`).
- **Миграция** `2026_09_02_190253_add_examples_title_to_categories_table` —
  в `categories` добавлен настраиваемый заголовок `examples_title` (nullable).
- **app/Models/Category.php** — отношения `albums()` (BelongsToMany через
  `category_album`) и `featuredAlbum()` (BelongsTo); `$fillable` и `$casts`
  дополнены новыми полями.
- **app/Models/Album.php** — обратное отношение `categories()` (BelongsToMany).
- **app/Filament/Resources/Categories/Schemas/CategoryForm.php** — раздел
  «Примеры работ»: TextInput `examples_title`, multi-select `albums`, Toggle
  `show_album_photos` «Показать первый альбом блоком с фото», Select
  `featured_album_id`.
- **app/Http/Controllers/ServiceCatalogController.php** — `showCategory()` грузит
  альбомы категории и (при включённом переключателе) `featuredAlbum` с `photos.media`,
  исключая выбранный альбом из карточек (как у услуги).
- **resources/views/services/category.blade.php** — секция «Примеры работ»
  (сетка карточек альбомов, заголовок `examples_title` с фоллбэком «Примеры работ»)
  и блок фото выбранного альбома через `<x-site.album-photos>`.

### Документация
- Обновлены `database.md` (таблица `category_album`, новые поля категорий, связи)
  и `architecture.md` (раздел альбома на странице категории).

### Тесты
- `ServiceCatalogControllerTest` — 32/33 проходят; падение
  `test_category_page_shows_cover_image` — известное независящее падение среды
  Media/Storage (не связано с этой задачей).

---
## 2026-09-02 — Запрет включения звука, когда звук отключён в админке

### Изменено
- **resources/views/components/site/video-player.blade.php**: при `has_sound = false`
  на загруженных видео (и повёрнутых, и обычных) на `<video>` теперь добавляется
  атрибут `data-video-forbid-sound` (вместе с `muted`). У обычных (неповёрнутых)
  видео в `controlsList` добавлен `noplaybackrate`.
- **resources/js/app.js**: для всех `video[data-video-forbid-sound]` принудительно
  устанавливается `muted = true`, а слушатели `volumechange`/`play`/`loadedmetadata`
  заново приглушают видео — пользователь физически не может включить звук через
  нативные контролы: кнопка mute/громкость не дают эффекта.
- **app/Models/Video.php**: `embed_url` теперь учитывает `has_sound`. Когда звук
  отключён, в URL встраиваемого плеера добавляется параметр приглушения:
  - YouTube → `?mute=1`;
  - Vimeo / Rutube → `?muted=1`;
  - VK (`video_ext.php`) → `&muted=1`.

Такое поведение распространяется на все типы плеера: кастомный (повёрнутые),
нативный (неповёрнутые) и встраиваемые (YouTube/Vimeo/Rutube/VK).

### Тесты
- **tests/Unit/Models/VideoModelTest.php** (+6): muted-параметры в embed_url
  при `has_sound = false` для YouTube/Vimeo/Rutube/VK и их отсутствие при
  `has_sound = true`.
- **tests/Feature/Http/Controllers/VideoControllerTest.php**: тесты muted-рендера
  дополнены проверкой наличия/отсутствия `data-video-forbid-sound`.
- Итого тесты прошли, кроме предшествующего независящего падения
  `ServiceCatalogControllerTest::test_category_page_shows_cover_image` (среда
  Media/Storage, не связано с этой задачей).

### Документация
- Обновлён `architecture.md`.

## 2026-09-01 — Ленивая подгрузка видео и кэширование браузером

### Добавлено
- **resources/views/components/site/video-player.blade.php**: `preload` изменён
  с `none` на `auto` для обоих загруженных `<video>` — браузер сразу после
  загрузки страницы подтягивает метаданные и часть видео (на странице не больше
  трёх видео), воспроизведение стартует мгновенно.
- **app/Http/Controllers/VideoController.php**: `Cache-Control` для `video.stream`
  изменён с `private, no-store` на `private, max-age=86400, immutable`. Браузер
  хранит видео в собственном кэше (недоступно CDN/прокси), а ETag + `If-None-Match`
  дают дешёвую ревалидацию (304) — повторное открытие страницы не перекачивает файл.

### Тесты
- **tests/Feature/Http/Controllers/VideoControllerTest.php**: уточнены проверки
  заголовка кэша (`private`, `max-age=86400`).
- Итого 594 теста — все пройдены.

### Документация
- Обновлён `architecture.md` (кэширование браузером, `preload="auto"`).

## 2026-09-01 — Мгновенное воспроизведение видео: поддержка HTTP Range в потоке

### Исправлено
- **app/Http/Controllers/VideoController.php** — `stream()` теперь поддерживает
  HTTP Range-запросы, из-за отсутствия которых браузер ждал весь файл (~41 МБ)
  до начала воспроизведения:
  - одиночные диапазоны `bytes=start-end`, открытые `bytes=n-`, суффиксные
    `bytes=-n`, множественные `bytes=a-b,c-d` → `206 Partial Content`;
  - множественные диапазоны отдаются как `multipart/byteranges`;
  - неудовлетворимый диапазон → `416` + `Content-Range: bytes */size`;
  - `If-None-Match` (ETag) → `304 Not Modified`;
  - полнотелый запрос без Range → `200` с `Content-Length`;
  - тело стримится локально (`fopen` + `fseek`/`fread`, чанки 8 КБ);
  - защитные заголовки (`Cache-Control: private, no-store`, `X-Content-Type-Options:
    nosniff`, `Content-Disposition: inline`, `Accept-Ranges: bytes`) сохранены.

### Тесты
- **tests/Feature/Http/Controllers/VideoControllerTest.php** (+8): полнотелый
  200 с `Content-Length`, одиночный `206` с корректным слайсом и `Content-Range`,
  открытый и суффиксный диапазоны, множественный `206 multipart/byteranges`,
  416 на неудовлетворимый диапазон, 304 по `If-None-Match`.
- Итого 594 теста — все пройдены (предыдущий прогон 588).

### Документация
- Обновлён `architecture.md` (раздел поддержки Range в `video.stream`).

## 2026-09-01 — Вращение видео на ±90°, управление звуком и запрет скачивания

### Добавлено
- **Миграция `add_has_sound_to_videos_table`**: поле `videos.has_sound`
  (BOOLEAN, default true) — «С сайта звук должен звучать».
- **app/Models/Video.php**: `has_sound` в fillable + cast boolean.
- **VideoForm** (Filament): Toggle «С сайта звук должен звучать»; при
  выключении видео на страницах сайта воспроизводится без звука (muted).
- **VideosTable**: бейдж «Звук» (IconColumn boolean).
- **resources/views/components/site/video-player.blade.php**: при
  `has_sound = false` на `<video>` добавляется атрибут `muted` (для повёрнутых
  и обычных загруженных видео; звук включается/выключается только в админке,
  кнопки в плеере нет).
- **VideoController::index()** и **VideoFactory**: `has_sound` в выборку/дефолт.
- **Миграция `replace_rotate_90_with_rotation_in_videos_table`**: вместо булева
  `videos.rotate_90` введено поле `videos.rotation` (INT, default 0): `0` — без
  поворота, `90` — по часовой, `-90` — против часовой. Backfill: `rotate_90 = true`
  → `rotation = 90` (видео id 5 стало `rotation = 90`). Обратная миграция
  восстанавливает `rotate_90` из `rotation = 90`.
- **app/Models/Video.php**: `rotation` в fillable + cast integer; метод `isRotated()`
  (`rotation !== 0`); `source_url` указывает на прокси-роут `video.stream`.
- **VideoForm** (Filament): вместо Toggle — Select «Поворот» (Без поворота /
  90° по часовой / 90° против часовой).
- **VideosTable**: бейдж поворота (— / 90° / -90°).
- **resources/views/components/site/video-player.blade.php**: в `x-site.video-player`
  поддержан поворот и на `-90°` (CSS `rotate(-90deg)`); для повёрнутых видео —
  кастомный плеер (Play/Pause, прогресс-бар, таймкод) без нативных контролов.
- **Контейнеры**: у повёрнутых видео — `aspect-video`, у неповёрнутых
  вертикальных — `aspect-[9/16]` (home, раздел video, блок `x-site.videos`).
- **VideoController::index()** и **VideoFactory**: инфраструктура переведена
  на `rotation`.

### Тесты
- **tests/Feature/Http/Controllers/VideoControllerTest.php**: тесты поворота
  переведены на `rotation`; добавлен тест поворота против часовой
  (`rotate(-90deg)`), проверка, что `data-video-player` не рендерится при 0,
  а также muted-рендер при `has_sound = false` и его отсутствие при включённом звуке.
- **tests/Feature/Filament/VideoResourceTest.php**: create/update через форму
  с `rotation` (±90) и `has_sound`.
- Итого 588 тестов — все пройдены (предыдущий прогон 586).

### Документация
- Обновлён `database.md` (поле `videos.rotation` и `videos.has_sound`, значения, поведение).
- Обновлён `architecture.md` (роут `video.stream`, раздел «Плеер видео:
  поворот ±90° и запрет скачивания», компонент `video-player`).

## 2026-09-01 — Показ альбома всеми фото на странице услуги

### Добавлено
- **Миграция `add_featured_album_fields_to_services_table`**: поля
  `services.show_album_photos` (boolean, default false) и
  `services.featured_album_id` (nullable, FK → `albums.id`, ON DELETE SET NULL).
- **app/Models/Service.php**: `show_album_photos`, `featured_album_id`
  в fillable + casts; связь `featuredAlbum()` (BelongsTo → `albums`).
- **ServiceForm** (Filament, раздел «Примеры работ»):
  - Toggle «Показать первый альбом блоком с фото»;
  - Select «Альбом для отображения блоком» (всегда видим, позволяет очистить
    выбор даже при выключенном toggle).
- **resources/views/components/site/album-photos.blade.php** — переиспользуемый
  компонент: сетка фото альбома + lightbox + JS (ранее дублировался во
  встроенном виде на странице альбома).
- **resources/views/portfolio/show.blade.php** — блок фото/lightbox заменён
  на `<x-site.album-photos :album="$album" />`.
- **resources/views/services/show.blade.php** — при `show_album_photos` +
  выбранном альбоме ниже секции «Примеры работ» выводится блок всех фото
  выбранного альбома (заголовок — название альбома) тем же компонентом.
- **ServiceCatalogController::showService()**:
  - при `show_album_photos = true` выбранный альбом исключается из списка
    карточек альбомов-примеров (карточки остаются, если ≥ 1);
  - грузит `featuredAlbum` (только опубликованный) + `photos.media`;
  - при выключенном toggle выбранный альбом остаётся обычной карточкой.
- **ServiceFactory**: дефолт `show_album_photos => false`.

### Тесты
- **tests/Feature/Http/Controllers/ServiceCatalogControllerTest.php** (+5): блок
  всех фото выбранного альбома, остальные альбомы карточками до сетки,
  toggle-off (альбом карточкой, lightbox скрыт), неопубликованный
  featured-альбом не показывается.
- **tests/Feature/Filament/ServiceFeaturedAlbumTest.php** (4): поля формы
  редактирования, сохранение услуги с featured-альбомом, очистка выбора
  (toggle off), сохранение при создании.
- Итого 578 тестов / 1527 утверждений — все пройдены.

### Документация
- Обновлён `database.md` (поля `services.*`, FK, индексы, описание поведения).
- Обновлён `architecture.md` (компонент `album-photos`, раздел про показ
  альбома всеми фото на странице услуги).

## 2026-09-01 — Поворот фотографий и изменение title в админке

### Добавлено
- **app/Actions/Media/RotateMedia.php** — поворот оригинала изображения
  по часовой стрелке на 90/180/270° через GD:
  - читает оригинал с любого диска (включая Яндекс.Диск) через Laravel Filesystem;
  - перезаписывает повёрнутый оригинал на том же диске (`disk`/`file_path` не меняются);
  - обновляет метаданные `width`/`height`/`file_size`;
  - очищает display/lightbox-кэш и пересобирает WebP-thumbnail + image-cache
    через `MediaProcessor::process(force: true)` — идемпотентно (`ImageCacheService::forget`);
  - сбои логируются с контекстом и не приводят к потере данных (запись Media
    остаётся пригодной для повторной обработки);
  - поддерживаются JPEG/PNG/WebP; некратные 90° углы, отсутствие файла
    и неподдерживаемые форматы возвращают `false`.
- **PhotosRelationManager** (страница редактирования альбома):
  - действие «Повернуть» — модалка с выбором угла (90° по часовой,
    180°, 90° против часовой), вызывает `RotateMedia` для media фотографии
    и показывает success/error-уведомление;
  - действие «Редактировать» — модалка теперь также позволяет изменять
    **title** фотографии (`media.title`) вместе с подписью и порядком.

### Тесты
- **tests/Feature/Actions/RotateMediaTest.php** (9): поворот на 90/180/270°,
  смена размеров оригинала/thumbnail/кэша, поворот на удалённом диске,
  некратный угол, нулевой угол, отсутствие файла, неподдерживаемый формат,
  сбой записи (оригинал и запись не изменяются).
- **tests/Feature/Filament/AlbumPhotosRelationManagerTest.php** (4): доступность
  действия «Повернуть», фактический поворот фото из альбома, изменение
  title/подписи/порядка через «Редактировать», сохранение title при
  изменении только подписи.
- Итого 570 тестов / 1492 утверждений — все пройдены.

### Документация
- Обновлён `architecture.md` (поворот фото в разделе Media Storage).

## 2026-09-01 — C1.1: связь Parent с клиентским альбомом

### Добавлено
- **Миграция `create_album_user_table`** — pivot `album_user` для many-to-many
  связи пользователей и альбомов:
  - `album_id` (FK → `albums.id`, ON DELETE CASCADE), `user_id`
    (FK → `users.id`, ON DELETE CASCADE), составной первичный ключ
    `(album_id, user_id)`; при удалении Album или User запись pivot удаляется
    автоматически
- **app/Models/Album.php**: связь `users()` (belongsToMany через `album_user`)
- **app/Models/User.php**: связь `albums()` (belongsToMany через `album_user`)

### Бизнес-правило
Связь `album_user` используется для назначения конкретного клиентского альбома
(`type = client`) пользователю `parent`. Parent не получает доступ к альбому
только потому, что тот находится в его проекте — требуется явная запись pivot.

### Тесты
- **tests/Feature/Models/AlbumUserTest.php** (7): User может быть связан с Album,
  Album может иметь пользователей, User может иметь albums, удаление Album
  удаляет pivot, удаление User удаляет pivot, один пользователь связан с
  несколькими альбомами, один альбом технически может иметь нескольких
  пользователей

### Не реализовано (границы задачи)
Policies, кабинет, комментарии, статусы проектов, интерфейс назначения
пользователя, изменение `projects`, новая система ролей — вне рамок C1.1.

### Документация
- **database.md**: добавлена таблица `album_user`, связи в ER-диаграмме
  (USERS ⟷ ALBUMS через ALBUM_USER)
- **architecture.md**: pivot `album_user` в списке pivot-таблиц (7 → 8)

### Проверка
- `php artisan test`: полный тестовый набор прошёл (см. Final report)
- Pint: см. Final report

## 2026-09-01 — Главная: блок «Наши услуги» — категории вместо услуг

### Изменено
- **app/Http/Controllers/HomeController.php**: вместо выборки всех опубликованных
  услуг теперь загружаются корневые категории услуг (`type = service`,
  `parent_id IS NULL`, `is_published`, `sort_order`) с обложкой
- **resources/views/home.blade.php**: блок «Наши услуги» отображает карточки
  категорий (обложка, название, описание, цена «от», кнопка «Подробнее»)
  вместо карточек услуг; добавлена кнопка «Все услуги» со ссылкой на
  `route('services.index')` внизу блока

### Тесты
- **tests/Feature/Http/Controllers/HomeControllerTest.php**:
  - `test_home_page_shows_services` → `test_home_page_shows_service_categories` —
    на главной отображается корневая категория услуг
  - `test_home_page_hides_unpublished_services` → `test_home_page_hides_unpublished_categories` —
    неопубликованная категория скрыта
  - добавлен `test_home_page_hides_child_categories` — дочерние категории
    не отображаются в корневом блоке
  - добавлен `test_home_page_shows_all_services_link` — кнопка «Все услуги»
    ведёт на `route('services.index')`

### Проверка
- HomeController + ServiceCatalogController тесты: 51/51 passed
- Pint: clean

## 2026-09-01 — B11 «Иерархический каталог услуг»: итоговый аудит и закрытие

### Аудит (без переписывания)
- Проверена функциональность иерархии `categories` (self-referencing `parent_id`),
  неограниченной глубины, полей категории, `ServiceCatalogResolver` / `ServiceCatalogController`,
  URL `/services/{path}`, breadcrumbs-компонента `<x-site.breadcrumbs>`, дерева в Filament,
  защиты от циклов и удаления категорий с детьми/услугами.
- Миграции этапа на месте: `add_hierarchy_fields_to_categories_table`,
  `create_category_video_table`, `create_category_service_item_table`.

### Исправлено (оптимизация запросов, N+1)
- **`ServiceCatalogController::index()`**:
  - дочерние категории грузятся с `parent` (устранена ленивая загрузка родителя
    при `$child->catalogPath()` на карточках подкатегорий);
  - услуги в категориях грузятся как `cover, items.icon, category`;
  - `servicesWithoutCategory` грузится как `cover, items.icon, category`;
    в `get([...])` добавлен столбец `category_id` (нужен для eager load `category`).
- **`ServiceCatalogController::showService()`**:
  - основные данные услуги грузятся с `items.icon` (список ServiceItems в шаблоне
    обращается к `$item->icon`);
  - список «Другие услуги» (`serviceList`) дополнен связью `category`
    (устранена ленивая загрузка при `catalogPath()` каждого элемента).

### Тесты (добавлено +3)
- **`tests/Feature/Http/Controllers/ServiceCatalogControllerTest.php`** — глубина 3:
  - `test_three_level_parent_page_hides_grandchild_services` — страница родителя не
    показывает услуги вложенных категорий;
  - `test_three_level_category_page_renders_full_breadcrumb` — полные breadcrumbs
    категории 3-го уровня;
  - `test_three_level_service_renders_full_breadcrumb` — полные breadcrumbs услуги
    на 3-м уровне вложенности.

### Документация
- **roadmap.md**: B11 отмечен как выполненный; следующий этап — «Этап 5. Кабинеты клиентов».

### Проверка
- Полный тестовый набор: 548/548 passed, 1383 assertions (+3 теста к этапу)
- Pint: clean

## 2026-08-29 — Категории: автогенерация slug

### Изменено
- **CategoryForm** (`/admin/categories`): добавлена автогенерация slug из названия
  по паттерну альбомов/страниц/статей/услуг:
  - поле `name` при изменении автоматически генерирует slug через `Str::slug()`
    с проверкой уникальности в рамках текущего `type` (UNIQUE(slug, type));
    при дубликате добавляется числовой суффикс (`-1`, `-2`);
  - ручное редактирование slug прекращает автогенерацию
    (флаг `_slug_manual` через `Hidden`-поле);
  - slug всегда приводится к slug-формату при редактировании

### Проверка
- Полный тестовый набор: 545/545 passed, 1375 assertions
- Pint: clean

## 2026-08-29 — Главная: hero на мобильных остаётся в кэше

### Изменено
- **resources/js/app.js**: подмена hero-картинки оригиналом выполняется только
  на экранах ≥ 768px (`matchMedia('(min-width: 768px)')`, совпадает с
  `md`-брейкпоинтом шапки). На мобильных загружается только display-кэш,
  оригинал не скачивается; при повороте/ресайзе на десктоп срабатывает `change`
  и оригинал подгружается

## 2026-08-29 — Главная: hero-картинка «из кэша, затем оригинал»

### Изменено
- **resources/views/home.blade.php**: image hero-блока (`#hero-block`) сначала
  грузится из кэша (`media.display`, 800px PNG) вместо оригинала; URL оригинала
  передаётся в `data-original` (fallback на оригинал, если кэш недоступен)
- **resources/js/app.js**: после загрузки кэшированной версии оригинал
  предзагружается через `new Image()` и подменяет `src` без мигания
  (защита от повторного свапа через `data-swapped`)

### Тесты
- **tests/Feature/Http/Controllers/HomeControllerTest.php**:
  - `test_home_hero_loads_cached_version_first_then_original` — hero использует
    `media.display` как `src` и содержит `data-original` с URL оригинала;
  - `test_home_hero_without_cache_falls_back_to_original` — при недоступном
    display-кэше (не image) используется оригинал без `data-original`

### Проверка
- Полный тестовый набор: 545/545 passed (+2)
- Pint: clean

### Добавлено
- **Миграция `create_category_service_item_table`**: pivot для many-to-many
  связи категорий и пунктов услуг (`category_id`, `service_item_id`,
  `is_included`, `sort_order`, PK, каскадное удаление)
- **app/Models/Category.php**: связь `items()` (belongsToMany через
  `category_service_item` с pivot `is_included`/`sort_order`, порядок — pivot)
- **CategoryForm** (`/admin/categories`): секция «Что входит» с мультиселектом
  пунктов и созданием новых прямо из формы (паттерн ServiceForm)
- **Публичная страница категории** (`resources/views/services/category.blade.php`):
  блок «Что входит» (список пунктов с иконками и состоянием «включено в цену»)
  после цены в интро-секции
- **app/Http/Controllers/ServiceCatalogController.php**: eager loading
  `items.icon` на странице категории

### Тесты
- **tests/Feature/Http/Controllers/ServiceCatalogControllerTest.php**:
  `test_category_page_shows_attached_items` — пункты категории выводятся на
  публичной странице
- **tests/Feature/Filament/CategoryTreeAdminTest.php**:
  `test_service_items_can_be_attached_via_form` — мультиселект сохраняет связь
  в pivot

## 2026-08-29 — Этап B11, часть 6: видео в категориях

### Добавлено
- **Миграция `create_category_video_table`**: pivot для many-to-many связи
  категорий и видео (`category_id`, `video_id`, PK, каскадное удаление)
- **app/Models/Category.php**: связь `videos()` (belongsToMany через
  `category_video`, порядок — `videos.sort_order`)
- **CategoryForm** (`/admin/categories`): секция «Видео» с мультиселектом
  существующих видео
- **Публичная страница категории** (`resources/views/services/category.blade.php`):
  блок «Видео» через `x-site.videos` (горизонтальные — inline, вертикальные —
  слайдер) между блоком услуг и формой заявки
- **app/Http/Controllers/ServiceCatalogController.php**: eager loading `videos`
  на странице категории

### Тесты
- **tests/Feature/Http/Controllers/ServiceCatalogControllerTest.php**:
  `test_category_page_shows_attached_videos` — видео категории выводится на
  публичной странице (title + YouTube-embed)
- **tests/Feature/Filament/CategoryTreeAdminTest.php**:
  `test_videos_can_be_attached_via_form` — мультиселект сохраняет связь в pivot

## 2026-08-29 — Этап B11, часть 5: фотографии подкатегорий на /services

### Изменено
- **Публичная страница `/services`** (`resources/views/services/index.blade.php`):
  - карточки подкатегорий приведены к виду карточек услуг: обложка
    (`aspect-[16/9]`, `object-cover`, зум при наведении), название,
    описание из категории (обрезанное по `line-clamp-2`, без HTML),
    цена «от N ₽» и ссылка «Подробнее»;
  - при отсутствии обложки у подкатегории показывается пустое серое поле
    (как у карточек услуг)
- **app/Http/Controllers/ServiceCatalogController.php**: в `index()` добавлена
  жадная загрузка `children.cover` (устранён N+1 при выводе обложек)

### Добавлено
- **tests/Feature/Http/Controllers/ServiceCatalogControllerTest.php**:
  тест `test_index_shows_child_categories_with_cover` — карточка подкатегории
  на главной услуг выводит обложку, ссылку на раздел и «Подробнее»

## 2026-08-29 — Этап B11, часть 4: дерево категорий в Filament

### Добавлено
- **app/Services/CategoryTreeService.php**: работа с деревом категорий услуг:
  - `flatten(type)` — плоское представление дерева (BFS «родитель → дочерние»)
    с глубиной, отступом и полным путём (`pathLabel`);
  - `options(?Category $exclude)` — варианты для выбора родителя: только
    категории `type = service`, без самой категории и всего её поддерева
    (защита от циклов на уровне UI);
  - `move(Category, offset)` — перенос категории внутри списка братьев
    (вверх/вниз) с перерасчётом `sort_order` всего списка
- **Дерево в CategoriesTable** (`/admin/categories`):
  - название с отступами по уровню вложенности и цветовым различием уровней,
    подпись с полным путём («Выпускные альбомы → Для школ»);
  - колонки: тип (бейдж), «Цена от» (формат `от N ₽`), «Опубликована»
    (быстрый ToggleColumn), `sort_order`, дата;
  - фильтры: «Тип» и «Статус публикации»;
  - сортировка по умолчанию по `sort_order`;
  - действия строки: редактирование, «Подкатегория» (создание дочерней с
    подставленным `parent_id`), «Переместить выше/ниже»;
  - массовое удаление убрано (опасно в иерархии) — удаление доступно только
    для безопасных категорий через карточку редактирования
- **CategoryForm**: добавлены `parent_id` (Select с деревом вариантов, скрыт
  для `type = post`), обложка `cover_media_id`, описание (RichEditor),
  «цена от» + примечание, SEO-блок, `is_published`, `sort_order`; для `type`
  задан default `service`, при переключении на `post` `parent_id` очищается;
  создание подкатегории предзаполняет родителя из `?parent_id=`

### Модель Category (защита корректности иерархии)
- `canBeDeleted()`: категорию можно удалить только если у неё нет дочерних
  категорий и услуг
- событие `deleting`: удаление категории с потомками/услугами запрещено
  (`LogicException`) — связанные услуги автоматически не удаляются
- событие `saving`: перевод категории `service` с потомками или услугами
  в `post` запрещён; смена `parent_id` по-прежнему защищена от циклов
  (`assertNotCyclic`), включая `parent_id = id`
- В EditCategory кнопка «Удалить» отключается с поясняющей подсказкой, когда
  категория не может быть удалена

### Тесты
- **tests/Feature/Services/CategoryTreeServiceTest.php** (7): порядок и глубина
  `flatten`, игнорирование `post`-категорий, включение/исключение вариантов
  родителя (исключение себя и поддерева), перенос вверх/вниз и перерасчёт
  `sort_order`, запрет сдвига на краю списка
- **tests/Feature/Models/CategoryHierarchyTest.php** (+6): удаление пустой
  категории разрешено, удаление с дочерними/услугами запрещено, перевод
  категории с потомками/услугами в `post` запрещён, листовая категория
  переводится свободно
- **tests/Feature/Filament/CategoryTreeAdminTest.php** (11, Livewire):
  создание корневой категории, создание дочерней, предзаполнение родителя из
  query-параметра, смена родителя, изменение `sort_order`, защита от
  `parent_id = id` и от цикла (через форму), сохранение новых полей
  (обложка/описание/цена/SEO/пибликация), отображение дерева в списке,
  действие «Переместить выше», отключённое удаление для категории с услугами

### Не изменено
- Схема БД не менялась (все поля добавлены ранее), `type = post` работает
  по-прежнему (плоские категории блога), публичный каталог не затрагивался

### Проверка
- Полный тестовый набор: 538/538 passed
- Pint: clean
- Ручная проверка сценариев админки: дерево в списке (отступы, путь),
  страница создания (поле «Родительская категория», варианты), страница
  редактирования (исключение собственного поддерева из вариантов родителя)

## 2026-08-29 — Этап B11, часть 3: хлебные крошки (проверка) и публичный интерфейс каталога

### Изменено
- **resources/views/components/site/breadcrumbs.blade.php**: очистка разметки —
  убран пустой служебный `<span></span>` перед сепаратором. Поведение и сепаратор
  (`&bull;`) не менялись: компонент принимает массив произвольной глубины
  (`label` + необязательный `url`), последний элемент без `url` отображается как
  текущая страница (`aria-current="page"`, без ссылки)
- Компонент `<x-site.breadcrumbs />` уже используется на страницах категорий,
  услуг, блога, портфолио и видео — дублирование HTML в шаблонах отсутствует

### Тесты
- **tests/Feature/Components/BreadcrumbsTest.php** (6): отрисовка цепочки
  произвольной глубины в заданном порядке, ссылки у промежуточных элементов,
  последний элемент не является ссылкой и помечен `aria-current="page"`,
  сепараторы между элементами, пусто при пустом списке, вариант `center`
- **tests/Feature/Http/Controllers/ServiceCatalogControllerTest.php**: полный
  иерархический breadcrumb на странице категории и услуги (`assertSeeInOrder`:
  Главная → Услуги → … → текущий), страница категории — обложка (img с alt),
  дочерние категории (карточки со ссылками и ценой), карточки услуг (заголовок,
  описание, цена, «Подробнее»), «Цена от» с примечанием, CTA/форма заявки
  (маршрут, поля, соглашение)

### Не изменено
- Бизнес-логика и схема БД не затрагивались
- Сепаратор хлебных крошек оставлен прежним (по указанию пользователя)

### Проверка
- Новые и расширенные тесты проходят без risky
- Полный тестовый набор и Pint — в конце сессии

## 2026-08-29 — Хлебные крошки и верхнее меню

### Изменено
- Переиспользуемый компонент **`<x-site.breadcrumbs />`** применён на всех
  страницах, кроме главной:
  - `services/category` и `services/show` уже использовали компонент;
  - `blog/show` — инлайн-навигация заменена на компонент
    (Главная • Блог • Категория • Пост);
  - `portfolio/show` — инлайн-навигация заменена на компонент
    (Главная • Портфолио • Альбом);
  - добавлены в hero-блоки `services/index`, `portfolio/index`, `blog/index`,
    `video/index` (Главная • Раздел) через новый проп `:center="true"`
  - проп `center` компонента центрирует крошки по горизонтали
- **Верхнее меню** (`components/site/header.blade.php`): ссылка на текущую
  страницу скрывается в десктопном и мобильном меню (сравнение по
  `request()->path()` с учётом вложенных URL, напр. на `/blog/история` ссылка
  «Блог» не показывается) — без циклических ссылок

### Тесты
- **tests/Feature/Http/Controllers/HeaderMenuTest.php** (3): ссылка на текущий
  раздел скрыта на странице раздела, на главной, на вложенной странице блога;
  остальные пункты меню на месте

## 2026-08-29 — Этап B11, часть 2: публичный иерархический каталог услуг

### Добавлено
- **app/Services/ServiceCatalogResolver.php**: отдельный resolver
  `resolve(array $segments): Category|Service|null` для иерархического пути:
  - каждый сегмент сначала разрешается как категория (`type = service`,
    `is_published`, `parent_id` = предыдущей категории, у корня `parent_id IS NULL`);
  - если дочерней категории нет и сегмент последний — разрешается как услуга
    этой категории (`is_published`);
  - сущность **не определяется по количеству сегментов**; при совпадении slug
    приоритет у категории (детерминированно);
  - неправильная цепочка родителей, неопубликованная/несуществующая категория
    или услуга, категория блога (`type = post`) → `null` (404);
  - поддерживаются услуги без категории на корневом уровне
- **app/Http/Controllers/ServiceCatalogController.php**: единый контроллер раздела
  `/services` (старый `ServiceController` удалён — логика перенесена без дублирования):
  - `index()` — корневые опубликованные категории (`sort_order`) с детьми и
    услугами непосредственного уровня + услуги без категории;
  - `show($path)` — разбивает путь, делегирует резолверу и рендерит страницу
    категории или услуги; `null` → 404 через `abort_unless`
- **routes/web.php**: `GET /services/{path}` с `where('path', '.*')` вместо
  `/{slug}` — путь категорий/услуги принимается целиком
- **Методы генерации URL**: `Category::catalogPath()` и `Service::catalogPath()`
  — иерархический slug-путь (`родитель/подкатегория/услуга`); все шаблоны строят
  ссылки `route('services.show', $model->catalogPath())`
- **resources/views/components/site/breadcrumbs.blade.php**: переиспользуемый
  компонент `<x-site.breadcrumbs :items="…" />` (массив `label`/`url`, последний
  без `url` — текущая страница)
- **resources/views/services/category.blade.php**: страница категории — breadcrumbs,
  обложка, title, описание, «Цена от», дочерние категории, услуги, CTA/форма заявки, SEO
- Шаблоны `services/index.blade.php` и `services/show.blade.php` адаптированы под
  иерархические URL; на странице услуги полный breadcrumb, остальной функционал сохранён

### Тесты
- **tests/Feature/Services/ServiceCatalogResolverTest.php** (16): корневая и
  вложенная категории, услуга во вложенной категории, услуга на корневом уровне,
  приоритет категории при совпадении slug, неправильная цепочка, несуществующие
  категории/услуги, неопубликованные категории (корневая, вложенная) и услуга,
  услуга в неопубликованной категории, `type = post` не резолвится, пустой путь
- **tests/Feature/Http/Controllers/ServiceCatalogControllerTest.php** (17): index
  с корневыми категориями и корневыми услугами, скрытие неопубликованного раздела,
  страница категории (контент, цена, SEO, CTA), вложенная категория с детьми и
  услугами, услуга во вложенной категории, сохранение функционала услуги (ServiceItems,
  альбомы, видео, форма), полный breadcrumb, неправильная цепочка и неизвестная
  категория → 404, неопубликованные категории/услуги → 404, услуга без категории,
  корректная генерация иерархического URL

### Изменено
- **app/Http/Controllers/HomeController.php**: в выборку `services` добавлен
  `category_id` — на главной ссылки строятся через `catalogPath()`
- **app/Models/Category.php** / **app/Models/Service.php**: добавлены `catalogPath()`

### Не изменено
- `Service`, `ServiceItem`, Media Storage, схема БД — не трогались
- Логика страницы услуги сохранена как была (расширена только ссылками и breadcrumb)
- Filament-дерево категорий — следующая часть этапа B11

### Документация
- **architecture.md**: обновлены структура приложения (контроллер, resolver,
  breadcrumbs, шаблоны), таблица маршрутов, новый раздел «Публичный каталог услуг —
  URL-резолвер и страницы (этап B11, часть 2)», раздел «Тестирование»

### Проверка
- Полный тестовый набор: 499 тестов проходят (+48), 1254 утверждений (+100)
- Pint: clean

## 2026-08-29 — Этап B11, часть 1: иерархия категорий каталога услуг

### Добавлено
- **Миграция `add_hierarchy_fields_to_categories_table`**: таблица `categories`
  расширена полями иерархии без замены существующей концепции и без отдельной
  таблицы `service_categories`:
  - `parent_id` — self-referencing FK → `categories.id` (ON DELETE SET NULL),
    глубина дерева не ограничена;
  - `cover_media_id` — FK → `media.id` (ON DELETE SET NULL);
  - `description`, `price_from`, `price_note`, `seo_title`, `seo_description`;
  - `is_published` (default true) — поля публикации в схеме категорий не было;
  - индексы `parent_id`, `cover_media_id`, `is_published`
- **app/Models/Category.php**:
  - отношения `parent()` (BelongsTo), `children()` (HasMany), `cover()` (BelongsTo);
  - существующие `services()`/`posts()` сохранены; `type`-политика без изменений
    (`service` — иерархические категории каталога, `post` — плоские категории блога);
  - методы `ancestors($withSelf = false)` (от корня к родителю),
    `path($withSelf = false)` (полный путь), `descendants()` (все потомки в глубину);
  - **защита от циклической иерархии** на уровне модели: хук `saving` вызывает
    `assertNotCyclic()`, когда `parent_id` изменён; запрещены выбор категории
    в качестве собственного родителя и цепочка `A → B → C → A`; обход
    `ancestors`/`descendants` также защищён от зацикливания на битых данных;
  - fillable и casts (`is_published`, `price_from`) обновлены
- **database/factories/CategoryFactory.php**: null-поля `parent_id`,
  `cover_media_id`, `is_published` по умолчанию

### Тесты
- **tests/Feature/Models/CategoryHierarchyTest.php** (13): parent/children,
  несколько уровней вложенности и неограниченная глубина дерева, category →
  services, cover_media (наличие и nullable), полный набор полей category
  (description/price/SEO/is_published), независимая работа service- и post-категорий,
  корневая категория, `ancestors`/`path`/`descendants`, запрет самородительства
  и потомка в качестве родителя, разрыв цепочки при откреплении

### Не изменено
- `Service`, `ServiceItem`, `ServiceController`, Media Storage и публичные
  страницы — не трогались
- Существующая миграция создания `categories` не менялась
- Filament-дерево категорий, страницы категорий, URL-резолвер и breadcrumbs
  — следующие части этапа B11

### Документация
- **database.md**: схема `categories` (поля, FK, индексы, методы модели),
  self-связь в ER-диаграмме
- **architecture.md**: раздел «Каталог услуг — иерархия категорий (этап B11,
  часть 1)», ссылки на тест в разделе «Тестирование»

### Проверка
- Миграция применена на dev-БД (MySQL): `php artisan migrate`
- Полный тестовый набор: 451+ тест проходят
- Pint: clean

### Добавлено
- **Таблица `icons`**: справочник файловых иконок (SVG/PNG), хранимых локально
  на диске `public` в директории `icons/`. Поля: `name`, `file_path`, `disk`
- **Миграция `add_icon_id_and_subtitle_to_service_items`**: добавлены поля
  `icon_id` (nullable FK → `icons.id`, SET NULL) и `subtitle` (nullable varchar 255)
  в таблицу `service_items`
- **app/Models/Icon.php**: Eloquent модель с `getUrl()` (через Storage),
  `serviceItems()` HasMany
- **app/Models/ServiceItem.php**: добавлены `icon_id`, `subtitle` в fillable,
  relationship `icon()` BelongsTo
- **app/Filament/Resources/Icons/**: полный CRUD для управления иконками
  - `IconResource.php`, `IconForm.php` (name + file upload на public disk),
    `IconsTable.php` (превью + название + путь), Create/Edit/List страницы
- **app/Filament/Resources/ServiceItems/Schemas/ServiceItemForm.php**: добавлены
  `Select` для иконки и `TextInput` для подзаголовка
- **app/Filament/Resources/ServiceItems/Tables/ServiceItemsTable.php**: добавлены
  колонки иконки (ImageColumn) и подзаголовка
- **app/Filament/Resources/Services/Schemas/ServiceForm.php**: в `createOptionForm`
  для пунктов добавлены поля иконки и подзаголовка
- **database/factories/IconFactory.php**: фабрика для тестов
- Тесты обновлены под новую схему

### Изменено
- **resources/views/services/show.blade.php**: при наличии иконки показывается
  вместо SVG-галочки; подзаголовок отображается после названия
- **resources/views/services/index.blade.php**: аналогично + **исправлен баг**:
  `$item->is_included` → `$item->pivot->is_included` в секции услуг без категории
  (pivot-поле `is_included` не принадлежит модели `ServiceItem`)

### Документация
- **database.md**: добавлена таблица `icons`, обновлена ER-диаграмма,
  добавлены `subtitle` и `icon_id` в `service_items`
- **architecture.md**: добавлен раздел «Icons — иконки пунктов услуг»,
  обновлена структура приложения (Icon модель, Icons ресурс)

## 2026-08-25 — Этап B10: финальная стабилизация Media Storage

### Code review
Проведён полный code review Media Storage (B1–B9):
- Media, MediaObserver, MediaProcessor, ProcessMedia Job
- ImageCacheService, DeleteMedia Action
- MigrateMediaToYandexDisk Action, MediaMigrateToYandex command
- CheckMediaIntegrity Action, MediaCheck command
- Filament: EditMedia, MediaTable (bulk delete), MediaResource
- MediaController (proxy routes), Photo/Album models
- config/filesystems.php

Результат: **архитектура стабильна, кодовых исправлений не требуется.**

### Failure scenarios (A–J)

| Сценарий | Существующая защита | Решение |
|---|---|---|
| A. Yandex недоступен при upload | MigrateMediaToYandexDisk::upload ловит Throwable → FAILED, БД не меняется, local original сохранён | OK |
| B. Yandex недоступен при ProcessMedia | processOrFail() пробрасывает → retry (3 попытки, backoff 30/120 сек) | OK |
| C. Thumbnail generation failed | catch Throwable + warning; metadata сохраняются; isPending() = true → retry доступен | OK |
| D. ProcessMedia запускается повторно | needsProcessing() проверяет заполненность полей и наличие файлов →noop | OK |
| E. Media удаляется во время ProcessMedia | find($id) → null → warning, job завершается; thumbnail на диске — безвредный orphan | OK |
| F. Media заменяется во время обработки | Job читает свежие данные → обрабатывает актуальный файл | OK |
| G. Migration останавливается посередине | Каждая Media обрабатывается изолированно; уже мигрированные пропускаются (идемпотентность) | OK |
| H. Bulk delete: часть Yandex удалена, часть нет | Каждая запись обрабатывается независимо; упавшие → record сохранён, stats показывают failed | OK |
| I. Bulk delete: «Не удалять Yandex originals» | $deleteRemoteOriginal = false → local + derivatives удалены, remote сохранён, record удалён | OK |
| J. После удаления Media на Yandex остаётся original | Ожидаемое поведение (B6); media:check показывает «Potential orphan» | OK |

### Проверка política удаления

- **Local**: delete Media → local original удалён → derivatives удалены → record удалён ✓
- **Yandex + Yes**: delete Media → remote original удалён → derivatives удалены → record удалён ✓
- **Yandex + No**: delete Media → remote original сохранён (orphan) → derivatives удалены → record удалён ✓
- **Bulk**: одно решение пользователя применяется ко всей выборке ✓
- **Ошибка Yandex delete**: Media НЕ удаляется (запись сохранена) ✓

### Проверка orphan semantics

- Media отсутствует + Yandex original существует → не вызывает автоматическое удаление ✓
- `media:check` показывает «Potential orphan files» (не ошибки) ✓

### Проверка migration

- После migration: `Media.disk = 'yandex_disk'` ✓
- B6 корректно применяет Yandex deletion policy ✓

### Проверка публичного сайта

- Портфолио: display-кэш для сетки, lightbox-кэш для просмотра ✓
- Альбомы: thumbnail для обложек услуг ✓
- Кнопка скачивания оригинала — только для авторизованных (`@auth` + `auth` middleware) ✓
- Yandex credentials не раскрываются (только proxy route) ✓
- Кэш: `Cache-Control: immutable` для кэшированных, `max-age=86400` для оригиналов ✓

### Проверка Filament

- Upload: через MediaResource, dispatch ProcessMedia после commit ✓
- Bulk upload: UploadPhotos в альбоме, по одному job на файл ✓
- Delete: EditMedia — две кнопки для Yandex (оставить/удалить) ✓
- Bulk delete: Radio Да/Нет, сводка результатов ✓
- Albums: cover, sorting, relation manager ✓
- Retry: Action «Повторить обработку» visible when isPending() ✓
- Import: ImportFromYandexDisk страница, ShouldBeUnique job ✓

### Документация
- **architecture.md**: добавлен раздел «Итоговая архитектура Media Storage (этап B10)»
  с 10 ключевыми правилами системы хранения
- **roadmap.md**: Этап 4 (B) помечен как завершённый, текущий этап — 5 (Кабинеты клиентов)

### Статистика
- Тесты: 451 проходят
- Assertions: 1154
- Pint: clean

## 2026-08-25 — Этап B9: проверка целостности Media Storage и orphan-файлы

### Добавлено
- **app/Actions/Media/CheckMediaIntegrity.php**: проверка одного Media record
  - DB → Storage: существует ли original на диске; thumbnail на диске `thumbnails`;
    кэш display/lightbox через `ImageCacheService::isCached()`; metadata
    (file_size, dimensions, file_size vs disk для локальных файлов)
  - Не скачивает originals для проверки metadata — использует быстрые операции
  - Ошибки storage не роняют проверку — логируются как warning
  - Каждому типу проблемы соответствует свой статус: `missing_original`,
    `missing_thumbnail`, `missing_image_cache`, `metadata_mismatch`, `valid`
- **app/Actions/Media/MediaCheckResult.php**: value class результата проверки
  с методами-предикатами (`isValid()`, `isMissingOriginal()` и т.д.)
- **app/Console/Commands/MediaCheck.php**: `php artisan media:check`
  - Проверяет все Media records (или конкретный через `--media-id=`)
  - Обнаруживает orphan-файлы на Яндекс.Диске (файлы без записи Media)
  - Orphan-файлы报告 как **Potential orphan files**, а НЕ как ошибки
    (пользователь мог сознательно оставить файл при удалении Media через B6)
  - Команда НИКОГДА не удаляет файлы — ни originals, ни thumbnails, ни Media
  - `--fix-thumbnails` — восстанавливает отсутствующие thumbnails через
    `MediaProcessor::process(force: true)`, не затрагивает originals
  - `--limit=N` — ограничение проверяемых записей
  - `--media-id=ID` — проверка конкретного Media
  - Итоговый отчёт: Checked / OK / Missing original / Missing thumbnail /
    Missing image cache / Metadata mismatch / Potential orphan Yandex files /
    Errors + таблица с детализацией по каждой записи
- Тесты: `tests/Feature/Actions/MediaCheckTest.php` (10) — valid, missing original,
  missing thumbnail, missing image cache, metadata mismatch (file_size, dimensions),
  non-image metadata, remote disk, empty disk;
  `tests/Feature/Console/MediaCheckCommandTest.php` (11) — полный отчёт, missing
  original, missing thumbnail, metadata mismatch, orphan, limit, media-id,
  fix thumbnails, mixed media summary

### Изменено
- **architecture.md**: раздел «Проверка целостности — media:check — этап B9»
  с описанием проверок DB→Storage, orphan-файлов, правил非автоудаления,
  опций команды и списка файлов
- **roadmap.md**: B9 отмечен как завершённый

### Не изменено
- Схема БД не менялась
- Команда не удаляет файлы по умолчанию
- Orphan-файлы не удаляются автоматически (осознанно)

### Статистика
- Тесты: 451 проходят (+21)
- Assertions: 1154 (+50)
- Pint: clean

## 2026-08-25 — Фикс: 500 на Select с media.title (NULL-заголовки)

### Исправлено
- **app/Models/Media.php**: добавлен аксессор `getTitleAttribute` — при NULL/пустом
  `title` возвращается basename из `file_path`. Ранее наличие записей media без
  заголовка (2 тестовые строки) роняло страницу `/admin/albums/{id}/edit` (и любые
  формы с `Select::relationship('cover'/'media', 'title')`) ошибкой
  `Select::isOptionDisabled(): Argument #2 ($label) must be of type string, null given`.
  Аксессор также чинит отображение в таблицах (`TextColumn::make('media.title')`).
  Данные БД не менялись.

## 2026-08-23 — Этап B8: миграция локальных оригиналов на Yandex Disk

### Добавлено
- **app/Actions/Media/MigrateMediaToYandexDisk.php**: миграция одного Media
  по критической последовательности upload → verify → DB update → delete local.
  Локальный оригинал гарантированно сохраняется до успешной проверки удалённого
  файла и обновления записи БД; сценарий «delete local → upload» невозможен
  - отбор кандидата: только `image/*` на локальных дисках (драйвер `local`);
    пропускаются записи уже на remote-дисках, производные (диски `thumbnails`,
    `image_cache`), неизвестные/чужие storage, без пути или MIME, с отсутствующим
    локальным файлом — каждая с явной причиной
  - загрузка стримом во временную копию на Диске (mkdir для вложенных путей);
    верификация: наличие → размер → sha256 содержимого (чтение обратно)
  - идемпотентность: `disk = yandex_disk` → skip; существующий remote-файл при
    совпадении размера и sha256 переиспользуется (без повторной загрузки),
    при расхождении — Failed «конфликт», чужой файл не перезаписывается;
    собственная непрошедшая верификацию загрузка удаляется с Диска (повторный
    запуск начисто), сбой удаления локального файла после update не откатывает
    миграцию (безвредный дубликат)
  - ключ кэша display/lightbox включает disk: старые варианты удаляются до
    смены disk (best-effort), новые генерируются лениво / через retry; thumbnail
    остаётся валидным (путь детерминирован от file_path)
- **app/Actions/Media/MediaMigrationResult.php**: результат операции
  (migrated / skipped / failed + причина + localDeleted)
- **app/Console/Commands/MediaMigrateToYandex.php**:
  `php artisan media:migrate-to-yandex [--dry-run] [--limit=] [--media-id=]`
  - команда занимается выборкой, batching, выводом и обработкой ошибок;
    логика одной записи — в Action
  - dry-run: ничего не меняет (БД/storage/Media); показывает найдено,
    доступно к миграции, пропущено и причины по каждой записи; конфликт
    с remote-файлом проверяется по метаданным размера, без скачивания
  - `--limit` применяется после отбора кандидатов; записи сверх лимита —
    пропущенные с причиной; `--media-id=` — одна запись (несуществующая → FAILURE)
  - изоляция сбоев: один проблемный Media не останавливает batch; статистика:
    обработано / мигрировано / пропущено / с ошибками / локально удалено;
    код возврата FAILURE при наличии ошибок

### Не изменено
- Схема БД не менялась (`database.md` дополнена примечанием в Storage Strategy)
- Массовая миграция не запускалась — только dry-run проверка на реальных данных
  (894 Media: 192 локальных, 702 уже на Диске, 2 без локального файла,
  1 без MIME); данные после dry-run не изменились
- Удаление Media после миграции работает по политике B6 (решение об
  удалении Yandex-оригинала за пользователем)

### Документация
- **README.md**: добавлены разделы по artisan-командам медиа-хранилища:
  `media:migrate-to-yandex` (B8), `media:prune-image-cache`, `media:test-storage`

### Статистика
- Тесты: 430 проходят (+22)
- Assertions: 1104 (+129)
- Pint: clean

## 2026-08-23 — Этап B7: Filament UX Media Storage

### Добавлено
- **Действие «Повторить обработку»** для незавершённой обработки Media:
  - `MediaTable` (действие записи) и `EditMedia` (header action) — видно только
    когда `MediaProcessor::isPending()` = true; повторно диспатчит Job
    `ProcessMedia` без дублирования логики обработки; HTTP-запрос тяжёлой
    работы не выполняет
  - **app/Services/MediaProcessor.php**: публичный метод `isPending(Media)` —
    единый источник состояния «обработка не завершена» (пустые метаданные,
    отсутствующий thumbnail или display/lightbox вариант)
- **Колонка «Обработка»** в списке Media («Готово» / «В очереди») — вычисляемое
  состояние через `isPending()`; поле status в БД сознательно не вводилось
- Тесты: `tests/Feature/Filament/MediaRetryProcessingTest.php` (6) — видимость
  retry в списке и на странице редактирования, dispatch ProcessMedia,
  корректность `isPending()`, завершение обработки при повторном запуске;
  `tests/Feature/Filament/MediaUploadTest.php` (1) — single upload через
  страницу создания Media: запись создана, обработка ушла в очередь;
  `tests/Feature/Models/MediaReuseSafetyTest.php` (4) — удаление Photo
  сохраняет Media и файлы, Media переиспользуем вторым альбомом, удаление
  Album сохраняет Media, удаление Media каскадно убирает Photo и обнуляет обложку

### Изменено
- **app/Filament/Resources/Media/Pages/EditMedia.php**: одиночное удаление
  Yandex-Media переведено с Toggle на две явные кнопки в модалке
  «Удалить файл с Яндекс-Диска?»:
  - «Удалить Media, оставить файл» — кнопка по умолчанию (безопасный вариант);
  - «Удалить Media и файл» — danger-кнопка (`makeModalSubmitAction`,
    аргумент `delete_remote_original`);
  - «Отмена» — стандартная; форма с checkbox убрана; для локальных дисков —
    прежнее стандартное подтверждение
- **app/Filament/Resources/Media/Tables/MediaTable.php**:
  - выбор судьбы Yandex-оригиналов при bulk delete переведён с Toggle на
    явное Radio «Да / Нет» (по умолчанию «Нет, оставить файлы на Яндекс-Диске»),
    виден только если среди выбранных есть оригиналы на Диске; одно решение
    применяется ко всей выборке
  - сводка после bulk: уведомление показывает удалено всего, из них вместе
    с оригиналами на Яндекс-Диске / с сохранением оригиналов и сколько не
    удалено из-за ошибки; stack trace не показывается (детали в журнале)

### Не изменено
- Политика удаления (`DeleteMedia`) — без изменений с этапа B6
- Массовая загрузка фото и импорт папки Яндекс.Диска — асинхронность уже
  обеспечена ProcessMedia/ImportAlbumFromYandexDisk (B4), покрыта тестами
- Удаление Photo/Album не затрагивает Media (проверено тестами, поведение прежнее)

### Статистика
- Тесты: 408 проходят (+13)
- Assertions: 975 (+78)
- Pint: clean

## 2026-08-24 — Этап B6: новая политика удаления Media и физических оригиналов

### Добавлено
- **app/Actions/Media/DeleteMedia.php**: единая точка политики удаления Media
  - Локальный оригинал удаляется всегда; оригинал на удалённом диске
    (Яндекс.Диск) — только при явном подтверждении (`deleteRemoteOriginal`)
  - Производные (WebP-thumbnail на диске `thumbnails` + display/lightbox
    на диске `image_cache`) удаляются в любом случае
  - **Критическое правило ошибок**: если запрошенный к удалению оригинал удалить
    не удалось (Диск недоступен, API вернул ошибку) — запись Media сохраняется,
    ошибка логируется (`Log::error` с media_id/disk/path), производные не трогаются.
    Сценарий «записи нет, файл есть» невозможен по воле системы
  - Ошибка удаления производных не блокирует удаление записи (несущественный
    кэш), логируется как warning; отсутствующий на диске оригинал удалению не мешает
  - При отказе от удаления Yandex-оригинала файл намеренно остаётся как
    потенциальный orphan — автоматическая очистка не выполняется (по условию)
- **ImageCacheService::forget()**: best-effort удаление display/lightbox вариантов,
  сбой одного варианта не мешает остальным
- Тесты: `tests/Feature/Actions/DeleteMediaTest.php` (7) — локальное удаление
  (файлы + запись), сбой локального оригинала → запись сохранена, Yandex с
  подтверждением, Yandex без подтверждения (orphan остаётся), сбой Диска при
  подтверждении → запись сохранена и производные целы, отсутствующий remote-файл,
  сбой удаления превью не блокирует запись;
  `tests/Feature/Filament/MediaDeletionTest.php` (5) — массовое удаление вперемешку
  local/Yandex с отказом и с подтверждением, частичный сбой из N (упавшая запись
  остаётся в БД), одиночное удаление local и одиночное с вопросом про Яндекс

### Изменено
- **app/Filament/Resources/Media/Pages/EditMedia.php**: DeleteAction кастомизирован
  - Для локального диска — обычное подтверждение «будут удалены запись и все файлы»
  - Для remote-диска — форма с Toggle «Удалить файл с Яндекс-Диска?»
    (по умолчанию выключен = оставить файл как потенциальный orphan)
  - Удаление через `DeleteMedia`; при неудаче — notification «Не удалось удалить
    оригинал файла. Запись сохранена…» вместо потери данных
- **app/Filament/Resources/Media/Tables/MediaTable.php**: DeleteBulkAction
  кастомизирован под массовое удаление с одним подтверждением
  - Одно модальное окно на всю выборку: описание показывает количество выбранных
    файлов и сколько оригиналов находится на Яндекс-Диске («В выбранных элементах
    находятся N оригиналов на Яндекс-Диске»); Toggle вопроса виден только если
    такие файлы есть среди выбранных (состояние через mountUsing + hidden-поле)
  - Один ответ применяется ко всей выборке: Да — Yandex-оригиналы удаляются,
    Нет — остаются (orphan), локальные оригиналы и производные удаляются всегда
  - Смешанные ошибки: каждая запись обрабатывается независимо; упавшие записи
    остаются в БД (`reportBulkProcessingFailure()`), пользователь получает
    уведомление «Удалено файлов: X из Y. Остальные записи сохранены…»
  - Добавлена колонка `disk` (toggleable) для наглядности места хранения
- **architecture.md**: раздел «Удаление Media (этап B6)»

### Не изменено
- `MediaObserver` остался тонким: политика удаления живёт в Action, а не в Observer
- Прямое `$media->delete()` мимо Action по-прежнему удаляет только запись БД —
  контракт: физические файлы удаляет только `DeleteMedia`
- Автоочистка orphan-файлов не реализована (осознанно, по условию задачи);
  обнаружение — задача команды проверки целостности (B9)
- Удаление Photo/Album записи Media не затрагивает (поведение прежнее)

### Статистика
- Тесты: 395 проходят (+12)
- Assertions: 897 (+143)
- Pint: clean

## 2026-08-23 — Прогрев display/lightbox через очередь + диагностика прод-воркера

### Добавлено
- **Генерация кэша display/lightbox привязана к очереди**: `ProcessMedia` после
  метаданных и WebP-thumbnail прогревает PNG-варианты (≤800px, ≤1600px) для
  изображений. Раньше варианты создавались синхронно при первом заходе посетителя
  на `media.display` / `media.lightbox` — большие фотографии заставляли ждать
  - `ImageCacheService::warmCached()` — генерация из уже скачанного temp-файла
    оригинала (переиспользуется в `MediaProcessor`) — без повторного скачивания
    с Яндекс.Диска; добавлены `isCached()` и выделен общий
    `generateFromTempFile()`
  - Ленивая генерация в контроллере осталась как fallback (LRU-вытеснение,
    ручная очистка, отставание воркера) — маршруты и представления не изменены
  - `needsProcessing()` считает отсутствие любого варианта незавершённой
    обработкой: пропущенные варианты досчитываются при retry Job'а
  - LRU-обрезка кэша переведена на best-effort: сбой листинга диска не роняет
    уже сгенерированный вариант

### Исправлено
- **TypeError на страницах редактирования** (`/admin/{albums,services,posts,pages}/…/edit`):
  `Select::isOptionDisabled(): Argument #2 ($label) must be string, null given`.
  Причина: Media с `title = NULL` (артефакт отладочного tinker-запуска) ломал
  Select обложки, читающий `media.title` всех записей
  - Все четыре формы с выбором обложки получили
    `getOptionLabelFromRecordUsing()` — записи без заголовка отображаются как
    «Медиа #id», падение исключено независимо от данных
  - Данные исправлены: существующим Media с пустым заголовком проставлен title

### Диагностировано на сервере (воркер супервизора)
- Симптом «Папка [ppp] не найдена» при существующей папке: воркер, запущенный до
  деплоя пагинации, держал старые классы в памяти и видел только первые ~20
  папок корня Диска; SSH-запуск нового процесса работал. Решение:
  `sudo supervisorctl restart laravel-worker:*`; после деплоя — обязательный
  `php artisan queue:restart`

### Замечено окружение (dev)
- `storage/app/image-cache` был создан php-fpm (`www-data`, 700): CLI-команды
  от другого пользователя не могут листать каталог — LRU-обрезка падает.
  Требуется выровнять владельца/права (см. задачу пользователю)

### Статистика
- Тесты: 384 проходят (+3 за день)
- Assertions: 755
- Pint: clean

## 2026-08-22 — Импорт с Яндекс.Диска: пагинация листинга и асинхронный импорт

### Исправлено
- **Листинг Яндекс.Диска возвращал только 20 элементов**: API отдаёт содержимое
  папки страницами (лимит по умолчанию 20), а `listContents()` вендорского адаптера
  читал только первую страницу. Из-за этого импорт альбома из папки на 153 файла
  создавал всего 20 Media
  - **app/Filesystem/YandexDiskPaginatedAdapter.php**: подкласс вендорского адаптера,
    `iterateFolderContents()` идёт чанками по 100 (`PAGE_SIZE`) с offset-пагинацией
    до `_embedded.total`; при отсутствии total — до неполной страницы.
    Deep-листинг оставлен на поведении вендора (в коде не используется)
  - Драйвер `yandex-disk` в `AppServiceProvider` теперь создаёт этот адаптер;
    исправление действует для всех вызовов `files()`/`directories()` (импорт,
    каскад выбора папок, `media:test-storage`)

### Добавлено
- **Защита от двойной отправки формы**: Job `ImportAlbumFromYandexDisk` реализует
  `ShouldBeUnique` (uniqueId = md5(disk|type|folder)) — повторная отправка с теми
  же параметрами, пока первый импорт в очереди или выполняется, молча отбрасывается.
  Тесты: uniqueId зависит от диска/типа/папки; дубликат dispatch не создаёт второй
  задачи в таблице jobs (2 одинаковых → 1 строка)
- **app/Jobs/ImportAlbumFromYandexDisk.php**: импорт альбома выполняется очередью,
  не в HTTP-запросе. Job переиспользует существующий Action (без дублирования);
  tries=3, timeout=300, backoff [30,120]. Атомарность транзакции Action исключает
  дубликаты альбомов при retry
- **Filament ImportFromYandexDisk**: форма диспатчит Job и сразу сообщает
  «Импорт запущен» с редиректом на список альбомов; тяжёлая обработка фото —
  по-прежнему ProcessMedia (этап B4)
- **config/filesystems.php**: секция `yandex_import.max_files`
  (env `YANDEX_IMPORT_MAX_FILES`, по умолчанию 500; раньше лимит был жёстко 100
  и молча обрезал папки больше 100 файлов)
- Тесты: пагинация адаптера (4) — следование `_embedded.total`, одна страница,
  остановка на неполной странице без total, продолжение на полной странице без total;
  Job импорта (3) — создание альбома/фото/Media/dispatch ProcessMedia,
  ошибка отсутствующей папки, параметры retry; страница (3) — dispatch Job вместо
  синхронного импорта, альбом в БД не создаётся в запросе

### Проверено на реальном Яндекс.Диске
- `Storage::disk('yandex_disk')->files('японки')` → 153 файла (2 запроса API:
  offset 0 → 100 записей, offset 100 → 53)
- После перезапуска воркера полный импорт папки «японки»: альбом, 153 Media + Photo,
  превью генерируются ProcessMedia

### Важно при деплое
- Воркер очереди держит код в памяти: после обновления кода выполнять
  `php artisan queue:restart`, иначе Job'ы обрабатываются старой версией классов

### Статистика
- Тесты: 381 проходят (+10)
- Assertions: 734
- Pint: clean

## 2026-08-21 — Этап B4: асинхронная обработка Media

### Добавлено
- **app/Jobs/ProcessMedia.php**: Queue Job обработки Media (metadata + WebP-thumbnail)
  - Параметры: `$tries = 3`, `$timeout = 180`, `backoff() = [30, 120]` сек,
    `$afterCommit = true`
  - Принимает `mediaId` (int): каждая попытка читает свежую запись из БД,
    удалённая Media не роняет job
  - Обработка делегирована существующему `MediaProcessor::processOrFail()` —
    без дублирования GD/mime-логики
- **MediaProcessor::processOrFail()**: аналогичен `process()`, но Throwable после
  логирования пробрасывается — временный сбой storage приводит к retry очереди.
  Общая логика (`handle()`, `reportFailure()`) не дублируется; `process()` сохранён
  для команды регенерации и CLI

### Изменено
- **app/Observers/MediaObserver.php**: `created` диспатчит `ProcessMedia::dispatch($media->id)`
  вместо синхронной `MediaProcessor::process()`; зависимость от процессора убрана.
  Dispatch — в единственной точке, после вставки записи: покрывает все пути создания
  Media (UploadPhotos/CreateAlbum, EditAlbum, ImportFromYandexDisk, MediaResource);
  массовая загрузка даёт по одному job на файл, HTTP-запрос не ждёт обработки
- Тесты, ассертирующие результат обработки по in-memory экземпляру:
  добавлен `refresh()` после `create()` — при асинхронной схеме состояние
  появляется в БД, а не у создающего объекта
  (MediaObserverTest, MediaProcessorRemoteStreamTest, MediaImageCacheTest)
- Тесты: `tests/Feature/Jobs/ProcessMediaTest.php` (11) — dispatch при создании и
  массовой загрузке (Queue::fake), dispatch только после commit / отбрасывание
  при rollback (реальный database-драйвер), успешное выполнение, идемпотентный
  повторный запуск, отсутствующая Media, отсутствие оригинала без retry,
  повреждённое изображение, retry временного сбоя storage (Throwable),
  параметры tries/backoff/timeout
- architecture.md: lifecycle с очередью, контракт Job и processOrFail

### Поведение
- Lifecycle: Upload → создание Media → Observer → ProcessMedia (после commit)
  → очередь → metadata + thumbnail → Ready. Тяжёлая обработка выведена из HTTP-запроса
- Транзакции: `afterCommit` исключает ситуацию «job отправлен, transaction rollback» —
  задание создаётся только для закоммиченной Media
- Идемпотентность: повторный запуск job не создаёт дубликатов thumbnail/Media и лишних
  записей БД (детерминированный путь превью, заполнение только пустых полей)
- Ошибки: отсутствующая Media или оригинал — job завершается без ошибки (без бесконечных
  retry); недоступность storage — исключение → retry очереди (3 попытки, backoff 30/120 c)
- Миграция существующих Media и проверка целостности — вне рамок этапа

### Статистика
- Тесты: 371 проходят (+11)
- Assertions: 710
- Pint: clean

## 2026-08-21 — Этап B3: переработка жизненного цикла Media

### Добавлено
- **app/Services/MediaProcessor.php**: централизованная обработка Media — единая точка lifecycle
  (создание записи, команда регенерации; в B4 — Queue Job)
  - Метаданные: MIME (`mime_content_type`), `file_size`, `width`/`height`
    для изображений (`getimagesize`) — оригинал читается с диска `Media::disk`
    через Laravel Filesystem (стримы → временный файл, работает с удалённым Яндекс.Диском)
  - Thumbnail: WebP 400px на локальный диск `thumbnails`; путь детерминирован:
    `{директория оригинала}/{имя}_thumb.webp` (исправлен баг старого кода:
    `ltrim($dir, 'thumbnails/')` портил имена директорий, например `images/` → `ges/`)
  - Идемпотентность: заполняются только пустые поля metadata; существующий thumbnail
    не пересоздаётся при наличии файла (кроме `force = true`); полностью обработанное
    Media повторный вызов не изменяет; `file_path`/`disk` процессором никогда не меняются
  - Ошибки логируются с контекстом (`media_id`, `disk`, `path`) и возвращают `false`,
    без тихой потери данных: отсутствующий оригинал, нечитаемый файл, повреждённое
    изображение (mime/size сохраняются, без размеров и превью), сбой записи thumbnail
    (метаданные сохраняются), недоступный storage (catch Throwable верхнего уровня)
  - Статусы обработки в БД не введены: «требует обработки» выводится из пустых полей
    и отсутствия файла thumbnail
- Тесты: `tests/Unit/Services/MediaProcessorTest.php` (14) — метаданные, размеры,
  thumbnail (landscape/portrait/root), детерминизм пути, повторная обработка (noop,
  без оригинала), регенерация при отсутствии файла и по force, ошибки (нет оригинала,
  нечитаемый стрим, повреждённый JPEG, storage недоступен, сбой записи превью)

### Изменено
- **app/Observers/MediaObserver.php**: переписан — только Observer-ответственности:
  `creating` задаёт `disk` по умолчанию из `filesystems.default_media_disk`;
  `created` однократно запускает `MediaProcessor::process()`. Вся GD/mime-логика удалена
- **app/Console/Commands/MediaRegenerateThumbnails.php**: обработка делегирована
  `MediaProcessor::process(force: true)`; выбор записей и `--dry-run` остались в команде.
  Убран дублирующий GD-код; `--force` теперь пишет thumbnail по детерминированному пути
  и исправляет путь в БД
- **tests/Unit/Observers/MediaObserverTest.php**: переписан под контракт Observer —
  disk по умолчанию из конфига, запуск обработки при создании, отсутствие реобработки при update
- **tests/Feature/Observers/MediaObserverRemoteStreamTest.php** →
  **tests/Feature/Services/MediaProcessorRemoteStreamTest.php** (переименован под сервис)
- **database.md**, **architecture.md**: описание нового lifecycle

### Поведение
- Lifecycle: Upload → создание Media → сохранение оригинала → `MediaProcessor::process()`:
  MIME → file_size → width/height → WebP-thumbnail 400px на диске `thumbnails` → Ready
- Обновление Media (title/collection) больше не проходит через обработку — как и раньше,
  но теперь это явный контракт Observer, покрытый тестом
- Удаление Media удаляет только запись БД; файлы остаются (очистка файлов — этап B6)
- Существующие записи Media не мигрировались (по условию задачи); legacy-пути превью
  исправляются командой `media:regenerate-thumbnails --force`

### Статистика
- Тесты: 360 проходят (+22)
- Assertions: 674
- Pint: clean

## 2026-08-22 — Кэш производных изображений (display / lightbox)

### Добавлено
- **app/Services/ImageCacheService.php**: ленивый кэш PNG-версий оригиналов
  - Уровни (`filesystems.image_cache.tiers`): `display` ≤800px (сетка альбома),
    `lightbox` ≤1600px (полноэкранный просмотр)
  - Генерация при первом запросе; источник — оригинал с любого диска (включая Яндекс.Диск)
    через временный файл; ключ `{tier}/{media_id}-{hash}.png`; повторные запросы — с диска
  - Вытеснение: при превышении `IMAGE_CACHE_MAX_MB` удаляются самые старые файлы
    (проверяется после каждой генерации и в команде очистки)
- **app/Console/Commands/MediaPruneImageCache.php**: `media:prune-image-cache [--stats|--all]`
- **config/filesystems.php**: диск `image_cache` + секция параметров; env `IMAGE_CACHE_DISK`, `IMAGE_CACHE_MAX_MB`
- **app/Models/Media.php**: аксессоры `getDisplayUrl()` / `getLightboxUrl()` (прокси-роуты кэша)
- Роуты: `GET /media/{media}/download` (attachment), `/display`, `/lightbox` — `App\Http\Controllers\MediaController`
  - Кэшированные ответы с `Cache-Control: public, max-age=31536000, immutable`
- Страница альбома `portfolio/show.blade.php`:
  - Сетка использует display-кэш вместо thumbnails 400px
  - Список альбомов `portfolio/index.blade.php`: обложки используют display-кэш вместо оригиналов
  - Lightbox получает ссылки на lightbox-кэш; кнопка «Скачать в оригинальном разрешении» (`media.download`)
  - Мобильные (<800px): lightbox открывает display-версию для быстрой первой загрузки,
    при смене ориентации подменяется на lightbox-версию; кнопка скачивания поднята над «Поделиться»
  - Подпись фото из БД (Photo.caption) показывается под изображением в lightbox
    (data-caption → #lightboxCaption, скрывается если пусто)
  - Скачивание оригинала только для авторизованных: `auth`-middleware на
    `media.download`, кнопка в lightbox рендерится через `@auth`
- Тесты: `tests/Feature/Http/Controllers/MediaImageCacheTest.php` (9) — генерация/переиспользование
  кэша, 404, скачивание оригинала, вытеснение по лимиту, команда;
  тест страницы портфолио на ссылки кэша

### Изменено
- Тесты портфолио: добавлен `test_show_links_lightbox_to_cache_and_marks_display_url`

### Статистика
- Тесты: 338 проходят
- Assertions: 585
- Pint: clean

## 2026-08-22 — Исправление: mime_content_type при импорте с Яндекс.Диска

### Исправлено
- **app/Observers/MediaObserver.php**: при создании Media на удалённом диске (Яндекс.Диск)
  падал `mime_content_type(): Failed identify data`
  - Причина: `readStream()` Яндекс-адаптера открывает сетевой поток через `fopen(download_url)`,
    его URI — URL загрузчика, а не локальный файл; наблюдатель использовал этот URI как путь к файлу
    для `mime_content_type()`, `getimagesize()` и GD
  - Решение: файл один раз скачивается во временный файл (`tempnam` + `stream_copy_to_stream`),
    метаданные и превью генерируются по нему; временный файл удаляется в `finally`
  - Бонус: вместо двух скачиваний оригинала (метаданные + превью) теперь одно
- Тесты: `tests/Feature/Observers/MediaObserverRemoteStreamTest.php` (3) — адаптер, отдающий
  стримы с URI `php://temp` (имитация удалённого диска): метаданные, WebP-превью, пропуск не-изображений

### Проверено на реальном Яндекс.Диске
- Загрузка тестового JPEG → readStream → временный файл → mime/dimensions определяются корректно,
  временный файл и тестовая папка удалены

### Статистика
- Тесты: 328 проходят
- Assertions: 541
- Pint: clean

## 2026-08-22 — Импорт альбома из папки Яндекс.Диска

### Добавлено
- **app/Filament/Resources/Albums/Pages/ImportFromYandexDisk.php**: страница `/admin/albums/import-yandex`
  (кнопка «Импорт из Яндекс.Диска» в списке альбомов)
  - Интерактивный выбор папки: каскад Select (верхний уровень → подпапка), списки кэшируются на 10 минут,
    кнопка «Обновить список папок» сбрасывает кэш
  - Toggle «Указать путь вручную» — TextInput с валидацией существования папки (для глубокой вложенности)
  - Поля альбома: название, тип, проект (для type=project), описание, «первое фото как обложка»
  - Dot-папки (`.git` и т.п.) скрыты — SDK не поддерживает пути, начинающиеся с точки
- **app/Actions/Album/ImportAlbumFromYandexDisk.php**: импорт изображений из папки Яндекс.Диска
  - Фильтрация по расширениям (jpg/jpeg/png/webp/gif), естественная сортировка по имени
  - Лимит файлов `filesystems.yandex_import.max_files` (по умолчанию 100), превышение пропускается и считается
  - Оригиналы остаются на Яндекс.Диске (`Media.disk = 'yandex_disk'`), обложка = первое фото (опционально),
    создание альбома/Media/Photo в одной транзакции; метаданные и превью заполняет MediaObserver через стримы
- **app/Http/Controllers/MediaController.php** + роут `GET /media/{media}/original` (`media.original`):
  прокси-отдача оригиналов с удалённых дисков (стриминг, Content-Type из Media, кэш-заголовки)
- **app/Models/Media.php**: `getUrl()` для remote-дисков (конфиг `remote => true`) возвращает прокси-роут;
  добавлен `isRemoteDisk()`
- Тесты: `tests/Feature/Actions/ImportAlbumFromYandexDiskTest.php` (6),
  `tests/Feature/Http/Controllers/MediaControllerTest.php` (3),
  `tests/Feature/Filament/ImportFromYandexDiskPageTest.php` (2)

### Изменено
- **resources/views/portfolio/show.blade.php**: сетка фото использует `getThumbnailUrl()` вместо оригинала
  (lightbox по-прежнему открывает оригинал) — снижает нагрузку на прокси при альбомах на Яндекс.Диске

### Статистика
- Тесты: 325 проходят
- Assertions: 534
- Pint: clean

## 2026-08-22 — Этап B2: подключение Yandex Disk

### Отступление от исходного задания
Пакет `arhitector/yandex dev-master` несовместим со стеком проекта:
его зависимость `laminas/laminas-diactoros ^2.17` не поддерживает PHP 8.4,
а официальный Flysystem-адаптер (`arhitector/yandex-disk-flysystem`) требует `league/flysystem ^1.0`,
тогда как Laravel 13 использует Flysystem 3.x.
По согласованию использован современный форк того же REST-адаптера:
`impressiveweb/yandex-disk-flysystem` + `impressiveweb/yandex-disk` (Flysystem ^3.0, PHP ^8.1, Guzzle 7).

### Добавлено
- **config/filesystems.php**: диск `yandex_disk` (драйвер `yandex-disk`, флаги `remote`, `throw`)
- **app/Providers/AppServiceProvider.php**: регистрация драйвера `Storage::extend('yandex-disk')`;
  корневая директория применяется как path-prefix клиента — все пути диска относительны ей
- **app/Console/Commands/MediaTestStorage.php**: команда `php artisan media:test-storage [--disk=]`
  - Проверяет конфигурацию диска и наличие токена, затем полный цикл:
    mkdir → запись → проверка наличия → чтение → сравнение → удаление → rmdir
  - Не изменяет реальные записи Media
- **.env.example**: `YANDEX_DISK_TOKEN`, `YANDEX_DISK_PATH_PREFIX` (по умолчанию `disk:/`),
  `YANDEX_DISK_ROOT` (по умолчанию `fotoskazka/originals`); секреты только в env
- Тесты: `tests/Unit/Filesystem/YandexDiskDriverTest.php` (5) — конфиг, резолв диска,
  применение root к префиксу клиента; `tests/Feature/Console/MediaTestStorageCommandTest.php` (4)

### Проверено на реальном Яндекс.Диске
- `php artisan media:test-storage` — полный цикл проходит (exit 0)
- Неглубокий листинг `directories()` (~0.9 c) возвращает корневые-относительные пути
- Ограничения зафиксированы: промежуточные папки нужно создавать до загрузки;
  рекурсивный листинг делает запрос на каждую подпапку (не использовать синхронно);
  пути с ведущей точкой не работают

### Статистика
- Тесты: 325 проходят
- Pint: clean

## 2026-08-21 — Команда перегенерации превью

### Добавлено
- **app/Console/Commands/MediaRegenerateThumbnails.php**: artisan-команда `media:regenerate-thumbnails` для массовой регенерации WebP-превью
  - Опции: `--dry-run`, `--force`, `--limit=`, `--id=`
  - Обрабатывает записи без превью, с битыми путями (`thumbnails/thumbnails/`, `/./`) и с отсутствующим файлом на диске (путь в БД корректный, файл пересоздаётся из оригинала)
  - `--dry-run` показывает причину обработки (`no thumbnail`, `broken path`, `file missing`)
  - Читает оригиналы через стримы с диска `Media::disk`, пишет превью на диск `thumbnails`
- **README.md**: документация команды `media:regenerate-thumbnails`

### Исправлено
- Регенерированы превью для 181 существующей записи Media (исправлены пути вида `thumbnails/thumbnails/./...`)
- Досозданы отсутствующие файлы превью для записей, добавленных после последней генерации

### Статистика
- Тесты: 306 проходят
- Assertions: 478
- Pint: clean

## 2026-08-21 — Этап B1: аудит и абстракция файлового хранения

### Изменено
- **config/filesystems.php**: добавлен диск `thumbnails` для локального кэша превью; добавлен конфиг `default_media_disk` (env `MEDIA_DISK`, по умолчанию `public`)
- **app/Models/Media.php**: добавлены аксессоры `getUrl()` (оригинал через Media::disk) и `getThumbnailUrl()` (превью через диск thumbnails)
- **app/Observers/MediaObserver.php**: переписан на стримы (`readStream`/`put`) без `path()`; превью всегда пишутся на диск `thumbnails`
- **app/Filament/Resources/Media/Schemas/MediaForm.php**: FileUpload использует `config('filesystems.default_media_disk')`
- **app/Actions/Album/CreateAlbum.php**: создание Media использует конфигурируемый диск
- **app/Filament/Resources/Albums/Pages/UploadPhotos.php**: FileUpload для обложки и фото использует конфигурируемый диск
- **app/Filament/Resources/Albums/Pages/EditAlbum.php**: экшен дозагрузки фото использует конфигурируемый диск
- **app/Filament/Resources/Albums/RelationManagers/PhotosRelationManager.php**: ImageColumn для превью использует диск `thumbnails`
- **app/Models/Video.php**: `source_url` и `embed_url` используют конфигурируемый диск через `getDefaultDisk()`
- **Все Blade-шаблоны** (home, services, portfolio, blog, video): заменены прямые `Storage::url()` на вызовы аксессоров моделей (`$media->getUrl()`, `$media->getThumbnailUrl()`, `$video->source_url`)

### Исправлено
- **tests/Unit/Observers/MediaObserverTest.php**: тесты превью теперь проверяют диск `thumbnails`
- **tests/Feature/UploadPhotosTest.php**: добавлен fake для диска `thumbnails`
- **tests/Unit/Models/VideoModelTest.php**: проверки URL обновлены на проверку наличия пути в URL
- **tests/Feature/Http/Controllers/VideoControllerTest.php**: проверка загруженного видео через `$video->source_url`

### Статистика
- Тесты: 306 проходят
- Assertions: 478
- Pint: clean

## 2026-08-21 — Тесты Filament ресурсов

### Добавлено
- 117 тестов для Filament админ-панели (14 ресурсов)
- `tests/Feature/Filament/` — тесты доступности страниц (list/create/edit), CRUD-операций через модели, отображения данных в таблицах
- `database/factories/FaqItemFactory.php`, `database/factories/SocialLinkFactory.php` — фабрики для FaqItem и SocialLink
- Трейт `HasFactory` добавлен в модели `FaqItem` и `SocialLink`

### Исправления
- `HomeControllerTest::test_store_inquiry_creates_inquiry` — исправлен `assertRedirect` для соответствия поведению контроллера
- Тесты уникальности slug/email переписаны для совместимости с SQLite in-memory

### Статистика
- Тесты: 189 → 306 ( increase +117)
- Assertion: 328 → 478 ( increase +150)

## 2026-08-21 — Аудит и исправление architecture.md

### Исправления
- Стек: PHP 8.4.22 → 8.4.24
- Структура `app/`: добавлены `VideoController`, `Controller`, директории Filament (FaqItems, NotificationSettings, SocialLinks, Videos)
- Модели: 13 → 17 (добавлены FaqItem, NotificationSetting, SocialLink, Video)
- Blade-шаблоны: добавлены `video/`, `emails/`, компоненты (inquiry-form, inquiry-modal, share-button, social-links, videos)
- Маршруты: добавлен `GET /video`
- Pivot-таблицы: добавлены album_video, service_video, post_video
- Тесты: 81/144 → 189/328
- Filament Resources: добавлен `VideosRelationManager`
- ViewComposerServiceProvider: добавлено описание `socialLinks` и `serviceList`
- БД: 16 бизнес-таблиц + 3 pivot → 18 бизнес-таблиц + 7 pivot

## 2026-08-21 — Аудит и исправление database.md

### Исправления
- `inquiries`: убран дубль `agreed_to_terms`, исправлен порядок колонок согласно миграциям
- `social_links`: `url` исправлен с VARCHAR(1000) на VARCHAR(255) (соответствует миграции); список иконок исправлен на 6 актуальных (instagram, telegram, whatsapp, vk, youtube, viber)
- `notification_settings`: добавлено отсутствующее поле `title VARCHAR(255) NULL`

## 2026-08-18 — Перевод элементов интерфейса админки на русский язык

### Заявки (Inquiries)
- `InquiryForm`: подписи `Service` → `Услуга`, `User` → `Пользователь`; варианты статуса `New/In Progress/Completed/Cancelled` → `Новая/В обработке/Завершена/Отменена`
- `InquiriesTable`: фильтр статуса — аналогичный перевод

### Медиа (Media)
- `MediaTable`: фильтр коллекций `Covers/Gallery/Avatars` → `Обложки/Галерея/Аватары`

### Публикации (Posts)
- `PostForm`: подпись `Cover` → `Обложка`, заголовок секции `Content` → `Содержание`

### Проекты (Projects)
- `ProjectForm`: подписи `Client` → `Клиент`, `Manager` → `Менеджер`; типы проектов и статусы переведены; секция `Description` → `Описание`
- `ProjectsTable`: фильтры типов и статусов — аналогичный перевод

### Роли (Roles)
- `RolesTable`: подпись `Users` → `Пользователей`

### Услуги (Services)
- `ServiceForm`: подпись `Cover` → `Обложка`, секция `Description` → `Описание`

### Отзывы (Testimonials)
- `TestimonialForm`: подпись `Photo` → `Фотография`

### Пользователи (Users)
- `UsersTable`: фильтр статуса `Active/Inactive` → `Активен/Неактивен`

---

## 2026-08-18 — Модальное окно «Оставить заявку»

### Blade-компонент
- Создан `resources/views/components/site/inquiry-modal.blade.php` (`x-site.inquiry-modal`) — модальное окно с формой заявки
- Подключается в `layouts/site.blade.php`, доступно на всех страницах
- Закрытие: кнопка ✕, клик по фону, клавиша Escape
- Автооткрытие при наличии flash-сообщения `success` (после успешной отправки)

### Шапка
- Кнопки «Оставить заявку` (десктопная и мобильная) вызывают модальное окно вместо скролла к `#inquiry-form`
- Используется `data-open-modal="inquiry"` для открытия

### Провайдеры
- `ViewComposerServiceProvider`: добавлен `View::share('serviceList', ...)` — список услуг доступен глобально для модального окна

---

## 2026-08-18 — Блок «Мы в соцсетях» на главной странице

### Blade-компонент
- Создан `resources/views/components/site/social-links.blade.php` (`x-site.social-links`) — переиспользуемый компонент соцсетей
- Два варианта отображения: `variant="section"` (центрированный блок с заголовком и подписью) и `variant="compact"` (иконки без заголовка для футера)
- Иконки вынесены из footer в компонент — единый источник SVG для обоих использований

### Фронтенд
- На главной странице (`home.blade.php`) добавлен блок «Мы в соцсетях» сразу после Hero-секции
- Футер (`footer.blade.php`) рефакторинг — вместо инлайн-кода используется `<x-site.social-links variant="compact" />`

---

## 2026-08-18 — Видео-слайдер (slick)

### Фронтенд
- Добавлены зависимости `jquery@3.7.1` и `slick-carousel@1.8.1`
- `resources/js/app.js`: импорт slick CSS/JS, инициализация слайдера на `[data-video-slider]`
  (slidesToShow: 3 / 2 / 1, infinite: false)
- `resources/css/app.css`: стили стрелок и слайдов `.video-slider` под тёмную тему
- Вертикальные видео переведены с `overflow-x-auto` на slick-слайдер в:
  `components/site/videos.blade.php`, `home.blade.php`, `video/index.blade.php`
  — полоса прокрутки убрана, добавлены стрелки навигации

---

## 2026-08-18 — Видео напрямую в услугах и блоге

### Изменения БД
- **Новая миграция**: `create_service_video_table` — pivot услуг ↔ видео (service_id, video_id)
- **Новая миграция**: `create_post_video_table` — pivot статей ↔ видео (post_id, video_id)

### Модели
- **Service**: добавлено отношение `videos()` (BelongsToMany, сортировка по `videos.sort_order`)
- **Post**: добавлено отношение `videos()` (BelongsToMany, сортировка по `videos.sort_order`)
- **Video**: добавлены отношения `services()` и `posts()` (BelongsToMany)

### Blade-компонент
- Создан `resources/views/components/site/videos.blade.php` (`x-site.videos`) — универсальный рендер видео:
  горизонтальные (aspect-video, заголовок) + вертикальные (snap-scroll 9:16), поддержка файлов и embed
- Используется на страницах: альбома (portfolio/show), услуги (services/show), статьи (blog/show)
  — дублирующийся HTML видео-секции удалён

### Filament
- **ServiceForm**: добавлена секция «Видео» — Select(multiple) привязки видео к услуге
- **PostForm**: добавлена секция «Видео» — Select(multiple) привязки видео к статье
- **VideoForm**: секция «Альбомы» переименована в «Привязка» — добавлены Select(multiple): Услуги, Статьи блога

### Контроллеры
- **ServiceController::show** / **BlogController::show** — eager load `videos`

### Тесты
- `tests/Unit/Models/ModelRelationshipsTest.php` — 5 тестов: service↔video, post↔video, video↔services, video↔posts, сортировка по videos.sort_order
- `tests/Feature/Http/Controllers/ServiceControllerTest.php` — 2 теста: показ горизонтальных/вертикальных видео на странице услуги
- `tests/Feature/BlogTest.php` — 2 теста: показ горизонтальных/вертикальных видео в статье
- Итого 189 тестов / 328 assertions — все пройдены

### Документация
- Обновлены `database.md` (service_video, post_video), `architecture.md` (pivot-таблицы), `changelog.md`

---

## 2026-08-18 — Видео в альбомах (аналогично фото)

### Изменения БД
- **Новая миграция**: `create_album_video_table` — pivot для many-to-many связи альбомов и видео
  - `album_id`, `video_id` (FK, CASCADE), `caption` (подпись в альбоме), `sort_order` (порядок)
  - PRIMARY KEY(album_id, video_id)

### Модели
- **Album**: добавлено отношение `videos()` (BelongsToMany через `album_video`, withPivot `caption`/`sort_order`, orderByPivot)
- **Video**: добавлено отношение `albums()` (BelongsToMany через `album_video`)
- **Video**: добавлен accessor `thumbnail_url` — превью для YouTube-видео (`img.youtube.com`), иначе null

### Filament
- **AlbumResource**: добавлен `VideosRelationManager` («Видео») рядом с «Фотографии»:
  - Таблица: название, формат (badge), подпись, порядок; reorderable по pivot `sort_order`
  - «Прикрепить видео» — модалка: выбор видео из активных + порядок (порядок подставляется из видео)
  - Действия строки: редактирование подписи/порядка (pivot), открепление
- **VideoForm**: добавлена секция «Альбомы» — Select(multiple) привязки видео к альбомам

### Публичный сайт
- **PortfolioController::show** — eager load `videos`
- **portfolio/show.blade.php**: секция видео в альбоме:
  - горизонтальные — список с заголовком (подпись альбома ?? название видео) + aspect-video плеер
  - вертикальные — snap-scroll 9:16 (`w-96`)
  - заголовок берётся из pivot-подписи, если она задана
  - пустое состояние — только когда нет ни фото, ни видео
- **BlogController::show / ServiceController::show** — eager load `albums.videos`
- **blog/show.blade.php, services/show.blade.php**: карточки альбомов показывают плейсхолдер с иконкой видео (и превью YouTube, если доступно), когда у альбома нет обложки и фото, но есть видео

### Тесты
- `tests/Unit/Models/ModelRelationshipsTest.php` — 3 теста: album↔video, video↔album, сортировка по pivot sort_order
- `tests/Unit/Models/VideoModelTest.php` — 4 теста `thumbnail_url` (YouTube watch/short, VK, загруженный файл)
- `tests/Feature/Http/Controllers/PortfolioControllerTest.php` — 3 теста: показ горизонтальных/вертикальных видео в альбоме, pivot-подпись как заголовок
- Итого 180 тестов / 313 assertions — все пройдены

### Документация
- Обновлены `database.md` (album_video, ER-диаграмма, описание видео), `architecture.md` (pivot-таблицы), `changelog.md`

---

## 2026-08-18 — Тесты бизнес-логики и VideoController

### Изменения кода
- **Video**: добавлен трейт `HasFactory` (для фабрики и тестов)

### Фабрика
- **VideoFactory** — новая фабрика для модели Video (title, url, type, sort_order, is_active, show_on_home)

### Тесты (42 новых, всего 170 тестов / 298 assertions)
- `tests/Feature/Http/Controllers/VideoControllerTest.php` — 9 тестов:
  - успешный ответ `/video`, заголовок страницы из Page (slug: video)
  - показ горизонтальных/вертикальных видео и их embed-URL
  - скрытие неактивных, сортировка по sort_order
  - загруженные видео (`<video>`), пустое состояние
- `tests/Unit/Models/VideoModelTest.php` — 21 тест бизнес-логики модели:
  - casts (is_active, show_on_home, sort_order)
  - `embed_url` для всех провайдеров: YouTube (watch/embed/youtu.be), Vimeo, Rutube, VK, VK Video ext, загруженный файл, пустой URL
  - `is_upload`, `source_url`
- `tests/Unit/Observers/PageObserverTest.php` — 4 теста инвалидации кэша (saved/deleted/created)
- `tests/Unit/Mail/NewInquiryMailTest.php` — 4 теста mailable (subject, шаблон, рендер данных, отсутствие услуги/даты)
- `tests/Feature/Console/MakeFilamentUserCommandTest.php` — 4 теста команды `make:filament-user` (создание админа, роль, создание роли, переиспользование роли)

### Покрытие
- Общее: строки 27.9% → 30.1%, методы 45.5% → 48.8%
- 100% покрытия: VideoController, Video, PageObserver, NewInquiryMail, MakeFilamentUserCommand
- Остаётся непокрытой админ-панель Filament (~110 файлов)

## 2026-07-23 — Исправление: лимит загрузки Livewire

### Исправлено
- **Livewire** `temporary_file_upload.rules` был `null` → `['required', 'file', 'max:102400']` (100 МБ)
- По умолчанию Livewire ограничивал загрузку 12 МБ, что приводило к 302 редиректу и ошибке JSON
- Опубликован конфиг `config/livewire.php`

## 2026-07-23 — Полноценный раздел видео

### Изменения БД
- **Новая миграция**: `add_show_on_home_to_videos_table`
- `videos.show_on_home` (boolean, default false) + индекс — флаг показа на главной

### Модель
- **Video**: добавлен accessor `source_url` (общий метод получения URL: файл или ссылка)
- **Video**: `show_on_home` в fillable и casts

### Filament: VideosResource
- **VideoForm**: добавлен Toggle `show_on_home`
- **VideosTable**: колонки `is_upload`, `show_on_home`; фильтр по `show_on_home`

### Новый раздел /video
- **VideoController** — загрузка всех активных видео + контент страницы из Pages (slug: video)
- **Маршрут** `GET /video` → `VideoController@index` (route name: `video.index`)
- **Blade-шаблон** `resources/views/video/index.blade.php`:
  - Hero-секция с заголовком и подзаголовком из Page
  - Горизонтальные видео — список с заголовком + aspect-video плеер
  - Вертикальные видео — горизонтальный snap-scroll
  - Поддержка загруженных файлов (`<video>`) и embed-ссылок (`<iframe>`)

### Меню
- **PageContentService::getMenuItems()** — в список включён slug `video`
- Страница с slug `video`, созданная в админке (Pages), автоматически появляется в меню

### Главная страница
- **HomeController** — загружаются только видео с `show_on_home = true`
- **home.blade** — используется `$video->source_url` вместо прямого Storage::url

### Документация
- Обновлены `database.md`, `changelog.md`

## 2026-07-23 — Соцсети, FAQ, кнопка «Поделиться»

### Новые сущности (БД + Filament)
- **Таблица `social_links`** — хранение ссылок на соцсети (name, icon, url, sort_order, is_active)
- **Таблица `faq_items`** — вопросы и ответы для секции FAQ (question, answer, sort_order, is_active)
- **Filament Resource `SocialLinks`** — управление соцсетями в админке (раздел «Контент»)
- **Filament Resource `FaqItems`** — управление FAQ в админке (раздел «Контент»)

### Blade-компонент «Поделиться»
- `resources/views/components/site/share-button.blade.php` — использует Web Share API, fallback на копирование ссылки в буфер обмена, второй fallback на VK Share
- Добавлен на:
  - `portfolio/show.blade.php` — под заголовком альбома и в лайтбоксе (для каждого фото)
  - `services/show.blade.php` — под заголовком услуги

### Footer
- Вывод социальных сетей из БД (`socialLinks`) с SVG-иконками
- Иконки: Instagram, Telegram, WhatsApp, VK, YouTube, Viber, Odnoklassniki, Dzen, Rutube
- 4 колонки вместо 3

### Главная страница
- Добавлена секция FAQ (accordion) между отзывами и формой заявки
- Чистый JS для аккордеона (без зависимостей)

### ViewComposerServiceProvider
- `$socialLinks` доступен глобально во всех шаблонах (с try/catch для миграций)

### HomeController
- Добавлена загрузка `$faqItems` и `$socialLinks` для home-страницы

### Документация
- Обновлены `database.md` (faq_items, social_links), `changelog.md`

## 2026-07-23 — Исправление: lightbox на странице портфолио

### Изменения
- Удалён `data-aos` с отдельных элементов `<a>` (фото-ссылок) в portfolio/show.blade.php
- Причина: AOS устанавливал `opacity: 0` + `transform` на фото-ссылках, делая их невидимыми и некликабельными во время задержки анимации
- Секция портфолио и контейнер сетки сохранены с `data-aos` — плавное появление всей секции, а не отдельных фото

## 2026-07-23 — Секции и карточки: тёмная тема + AOS анимации

### Изменения
- **app.js**: установлен и подключен AOS (Animate On Scroll) — `npm install aos`
- **app.css**: `bg-gray-50` переопределён на `#111111` для чередующихся секций
- **Все шаблоны**:
  - Вертикальные отступы секций: `py-16`/`py-20` → `py-24`
  - Карточки: убран `border`, заменён на `shadow-lg shadow-black/30`
  - Фон карточек: явный `bg-[#1a1a1a]` вместо `bg-white`
  - Hover карточек: `hover:bg-[#242424]`
  - Заголовки карточек: `text-white`, описания: `text-gray-400`
  - Акцент: `text-[#d4af37]` вместо `text-amber-600`
- **hero-секции** страниц: `bg-[#111111]` вместо `bg-gray-50`
- **AOS**: all sections and cards получили `data-aos="fade-up"` с задержками
- **header**: тёмный фон `bg-[#0a0a0a]/95`, текст `text-gray-300 hover:text-white`
- **footer**: `bg-[#050505]`, контакты `hover:text-[#d4af37]`
- **inquiry-form**: underlined-поля на тёмном фоне (border-b, transparent bg)
- **blog/show**: `prose-gray` → `prose-invert` для контента
- **success-сообщения**: `bg-green-50` → `bg-green-900/30` для тёмной темы
- **portfolio/index**: удалена старая CSS-анимация `fadeInUp`, заменена на AOS
- **services/index + services/show**: текст цен `text-[#d4af37]`, включенные пункты `text-gray-300`

## 2026-07-23 — Стилизация кнопок

### Изменения
- Все кнопки приведены к единому стилю: `px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg hover:opacity-90`
- Hero CTA: `px-10 py-4` + `shadow-xl`
- Поиск в блоге: компактный вариант `px-6 py-2 text-xs`
- Затронуты: services/index, header, inquiry-form, auth/login, blog/index, blog/show, home (hero)

---

## 2026-07-23 — Hero: полноэкранное фото + overlay

### Изменения
- **home.blade.php**: hero переделан — одно фото из homepage album на весь экран, градиент black→transparent, заголовок и CTA по центру
- **home.blade.php**: удалён старый JS параллакса (3 колонки + mousemove)
- **app.css**: старые hero-стили заменены на minimal (zoom-анимация фона), старый код сохранён как закомментированная опция
- **app.css**: hero-кнопка теперь `bg-gold text-black`

---

## 2026-07-23 — Тёмная тема + типографика

### Цветовая схема (тёмная тема)
- **app.css**: добавлены цвета `gold`/`gold-hover` в `@theme`
- **app.css**: тёмный фон (`#0a0a0a`), поверхность карточек (`#1a1a1a`), секции (`#141414`), бордеры (`#2a2a2a`)
- **app.css**: акцентный цвет — золото `#d4af37` вместо amber-600
- **app.css**: текст: заголовки `#f5f5f0`, основной `#a8a8a0`, muted `#888880`
- **app.css**: hero: градиент изменён на тёмный, кнопка — золото на тёмном фоне
- **app.css**: footer сохранён тёмным (`#111111`) с читаемым текстом
- Все изменения — CSS-оверрайды, без массовой замены Tailwind-классов в шаблонах

### Типографика
- h1/h2: `font-bold` → `font-normal tracking-wide` (Forum normal weight)
- h3: добавлен `tracking-wide`
- hero h3: `font-weight: 700` → `font-weight: 400`, `letter-spacing: 0.05em` → `0.1em`
- Затронуты все шаблоны: home, services, portfolio, blog, auth, cabinet, footer

---

## 2026-07-23 — Внедрение шрифтов Ubuntu + Forum

## 2026-07-03 — Контент страниц вынесен в CMS

### Изменения БД
- **Новая миграция**: `add_page_content_fields_to_pages`
- `pages.subtitle` (TEXT, nullable) — подзаголовок страницы
- `pages.home_title` (VARCHAR(255), nullable) — заголовок блока на главной
- `pages.home_subtitle` (TEXT, nullable) — подзаголовок блока на главной
- `pages.show_on_home` (BOOLEAN, default false) — показывать блок на главной
- `pages.home_sort_order` (INT, default 0) — порядок блока на главной
- `pages.menu_title` (VARCHAR(255), nullable) — название пункта меню

### Модели
- **Page**: добавлены новые поля в `$fillable`, добавлен `casts` для `show_on_home` (boolean)
- **Page**: подключён `PageObserver` (инвалидация кэша при сохранении/удалении)

### Сидер
- **PageSeeder** — добавлены записи с фиксированными slug: `home`, `services`, `portfolio`, `blog`
- Каждая запись содержит начальные заголовки, подзаголовки, SEO, настройки для главной и меню

### Сервис
- **PageContentService** — новый сервис с кэшированием:
  - `get(slug)` — получение опубликованной страницы по slug (Cache::rememberForever)
  - `getHomeSections()` — все блоки для главной, отсортированные по home_sort_order
  - `getMenuItems()` — пункты меню (menu_title ?? title) для шапки
  - `clearCache(slug?)` — сброс кэша

### Filament: PageResource
- **PageForm** — поля разделены на секции:
  - Основная информация (title, menu_title, slug)
  - Заголовок страницы (subtitle, cover, content)
  - Главная страница (show_on_home, home_sort_order, home_title, home_subtitle)
  - Альбомы, SEO
  - Поля блока главной скрываются при `show_on_home = false`

### Контроллеры
- **HomeController** — передаёт `$page` и `$homeSections` в Blade
- **ServiceController** — передаёт `$page` (slug: services)
- **PortfolioController** — передаёт `$page` (slug: portfolio)
- **BlogController** — передаёт `$page` (slug: blog)

### Blade-шаблоны
- Все захардкоженные заголовки заменены на `$page?->title`, `$page?->subtitle`
- Главная использует `$homeSections[slug]->home_title / home_subtitle` для блоков
- SEO-метаданные (title, meta_description) берутся из `$page->seo_title / seo_description`

### Меню сайта
- Header больше не содержит захардкоженных пунктов
- Пункты загружаются через ViewComposerServiceProvider (View::share)
- Название: menu_title (если заполнено), иначе title
- URL формируется по slug (home → /, остальные → /{slug})

### PageObserver
- При `saved` — сбрасывается кэш конкретной страницы
- При `deleted` — сбрасывается весь кэш страниц

### Тесты
- 81 тест, 144 assertions — все пройдены
- Pint чистый

---

## 2026-06-30 — Рефакторинг: форма заявки вынесена в Blade-компонент

### Blade-компонент
- Создан `resources/views/components/site/inquiry-form.blade.php` — `x-site.inquiry-form`
- Параметры:
  - `services` (Collection|null) — список услуг для `<select>`
  - `selectedServiceId` (int|null) — предвыбор в select
  - `hiddenServiceId` (int|null) — скрытый input (для страницы услуги)
  - `buttonText` (string, default: «Отправить заявку») — текст кнопки
  - Поддержка произвольных HTML-атрибутов через `$attributes->merge()`

### Изменения в шаблонах
- **home.blade.php**, **services/index.blade.php**, **services/show.blade.php**, **portfolio/show.blade.php**, **blog/show.blade.php** — дублирующийся HTML формы заменён на `<x-site.inquiry-form>`

---

## 2026-06-30 — Исправление ошибок отправки уведомлений при создании заявки

### Критические
- **HomeController::storeInjection()**: добавлено `$validated['status'] = 'new'` — при создании заявки через форму не передавался `status`, что вызывало SQL-ошибку (поле ENUM NOT NULL без default).
- **Новая миграция**: `add_default_status_to_inquiries_table` — установлен DEFAULT `'new'` на колонку `status`.

### Формы заявки (все 5 шаблонов)
- Добавлено обязательное поле **Email** (с валидацией на бэкенде)
- Добавлено опциональное поле **Желаемая дата съёмки** (`date`)

### Валидация
- `email` — required|email|max:255
- `shooting_date` — nullable|date

### Модели
- **Inquiry**: `notification_error` убран из `$fillable` (не должен быть массово назначаем)

### Чистка
- Удалён мёртвый класс `NewInquiryNotification.php` (не использовался — заменён на `NewInquiryMail`)

---

## 2026-06-29 — Очередь отправки уведомлений + повторная отправка

### Изменения БД
- **Новая миграция**: `add_notification_error_to_inquiries`
- `inquiries.notification_error` (TEXT, nullable) — сообщение об ошибке при отправке уведомлений

### Job
- **SendInquiryNotifications** — `app/Jobs/SendInquiryNotifications.php`
  - Отправляет email (через Mail) и Telegram в рамках одного job
  - При ошибке пишет сообщение в `inquiry.notification_error`
  - При успехе очищает поле (`null`)

### Observer
- **InquiryObserver** — упрощён: теперь только диспатчит `SendInquiryNotifications` в очередь

### TelegramNotifier
- Метод `sendMessage()` теперь выбрасывает исключение при неудаче (вместо тихого логирования)
- Ошибка перехватывается в job и записывается в `notification_error`

### Filament: InquiryResource
- **InquiryForm** — добавлено поле `notification_error` (textarea, disabled, видно только при наличии ошибки)
- **EditInquiry** — добавлена header-action «Отправить повторно» (warning, с иконкой PaperAirplane)
  - Очищает `notification_error`, диспатчит job, показывает success-уведомление
  - Видна только когда есть ошибка

### Тесты
- 81 тест, 144 assertions — все пройдены
- Pint чистый

---

## 2026-06-29 — Настройки уведомлений через админку

### Изменения БД
- **Новая таблица**: `notification_settings` (email_enabled, email_recipients, telegram_enabled, telegram_bot_token, telegram_chat_id)
- **Seeder**: `NotificationSettingSeeder` — дефолтная запись (email включён, telegram выключен)

### Модели
- **NotificationSetting**: новая модель с casts (boolean для enabled-полей)

### Filament
- **NotificationSettingResource** — CRUD для настроек уведомлений в разделе «Администрирование»
- Форма: секции Email (чекбокс + tags для получателей) и Telegram (чекбокс + токен + chat_id)
- Таблица: колонки получателей, иконки включения Email/Telegram

### Уведомления
- **InquiryObserver**: получатели email берутся из `notification_settings.email_recipients` (через запятую), а не из всех admin-пользователей
- **TelegramNotifier**: принимает botToken/chatId из настроек, с fallback на config/services.php
- **NewInquiryMail** — Mailable с markdown-шаблоном (`resources/views/emails/new-inquiry.blade.php`)
- Старый `NewInquiryNotification` (Notification) заменён на `NewInquiryMail` (Mailable) — отправка на произвольные email из настроек

### Тесты
- 10 тестов в InquiryTest (81 всего, 144 assertions)
- Добавлены тесты: отключение email не отправляет, отключение telegram не отправляет
- Все тесты создают `NotificationSetting` в setUp

---

## 2026-06-29 — Этап 3.5: Улучшение работы с заявками

### Изменения БД
- **Новая миграция**: `add_project_id_to_inquiries_and_contact_fields_to_projects`
- `inquiries.project_id` (BIGINT, NULL, FK → projects ON DELETE SET NULL) + индекс
- `projects.contact_name`, `projects.contact_phone`, `projects.contact_email` + индекс на phone

### Модели
- **Inquiry**: добавлено отношение `project()` (BelongsTo)
- **Project**: добавлено отношение `inquiry()` (HasOne, через `project_id`)
- **Project**: `contact_name`, `contact_phone`, `contact_email` добавлены в `$fillable`

### Action
- Создан `app/Actions/Inquiry/CreateProjectFromInquiry.php` — транзакционное создание проекта с копированием контактных данных из заявки

### Filament: InquiryResource
- **EditInquiry**: header-actions «Создать проект» (форма: название, тип, менеджер, клиент, дата) и «Открыть проект» (когда проект уже создан)
- **InquiryForm**: секция «Проект» с выводом названия, статуса, типа (только для чтения, когда проект привязан)
- **InquiriesTable**: колонки «Проект» (название) и «Есть проект» (Да/Нет badge), фильтр «Только с проектом / Только без проекта»

### Filament: ProjectResource
- **ProjectForm**: секция «Контактные данные (из заявки)» — contact_name, contact_phone, contact_email
- **EditProject**: header-action «Открыть заявку» (когда проект создан из заявки)

### Уведомления
- **Email**: `NewInquiryNotification` — письмо администраторам при создании заявки (имя, телефон, email, услуга, дата, комментарий, ссылка)
- **Telegram**: `TelegramNotifier` — сервис отправки через Telegram API (конфигурация через `TELEGRAM_BOT_TOKEN` + `TELEGRAM_CHAT_ID`); ошибки логируются, не ломают создание заявки
- **InquiryObserver**: автоматический вызов email и telegram уведомлений при создании заявки

### Архитектура
- Бизнес-логика вынесена в `app/Actions/Inquiry/CreateProjectFromInquiry.php`
- Уведомления в `app/Notifications/NewInquiryNotification.php` и `app/Services/TelegramNotifier.php`

### Тесты
- 8 тестов в `tests/Feature/InquiryTest.php`:
  - создание проекта из заявки, контактные данные, транзакция
  - email-уведомление отправляется
  - telegram-уведомление отправляется
  - ошибка telegram не ломает создание заявки
  - inquiry → project / project → inquiry relationships

### Документация
- Обновлены `database.md` (новые поля и индексы), `changelog.md`

---

## 2026-06-29 — Блог: альбомы-слайдер на детальной странице

### Изменения
- **BlogController::show()** — добавлен eager load `albums.cover`, `albums.photos.media`
- **blog/show.blade.php** — добавлен блок «Фотоальбомы» с горизонтальным snap-scroll слайдером между контентом и формой заявки; каждая карточка показывает обложку (или первое фото) и название, клик ведёт на `/portfolio/{slug}`
- Обновлён Vite-билд (новые Tailwind-классы: `snap-x`, `snap-mandatory`, `scrollbar-thin`)

---

## 2026-06-29 — Этап 3.4: Блог

### Выполнено
- Создан **BlogController** (`app/Http/Controllers/BlogController.php`):
  - `index()` — опубликованные посты с пагинацией (6), поиск (`q`), фильтр по категориям (`category`), сортировка по `published_at DESC`, eager loading cover
  - `show($slug)` — детальная страница поста, 404 для неопубликованных/будущих, сайдбар, форма заявки со всеми услугами
- Создана страница списка блога (`resources/views/blog/index.blade.php`):
  - Сетка 2 колонки с обложками, датами, заголовками, excerpt
  - Пагинация
  - Сайдбар: поиск, категории (с количеством постов), последние записи, сброс фильтров
- Создана детальная страница поста (`resources/views/blog/show.blade.php`):
  - Хлебные крошки (Главная / Блог / Категория)
  - Обложка (16:9), дата, заголовок, контент (prose)
  - Сайдбар: поиск, категории, последние записи
  - Форма заявки внизу с выбором услуги
- Заменены маршруты-редиректы на реальный контроллер в `routes/web.php`
- **Тесты**: 10 тестов в `tests/Feature/BlogTest.php`:
  - blog index — успешный ответ, показывает опубликованные, скрывает неопубликованные/будущие
  - blog index — фильтр по поиску, по категории
  - blog show — успешный ответ, 404 для неопубликованных/будущих, наличие формы заявки

---

## 2026-06-24 — Этап 2: Filament ресурсы (CRUD)

### Выполнено
- Установлен Filament 4 (v4.11.7)
- Создан админ-пользователь (admin@fotoskazka.ru)
- Созданы миграции для всех таблиц (14 шт.)
- Созданы Eloquent модели со связями (12 шт.)
- Засеяны роли (5 шт.)
- Созданы **Filament ресурсы** для всех сущностей:
  - **Пользователи** (`/admin/users`) — поиск, фильтры по статусу/ролям, bulk delete
  - **Роли** (`/admin/roles`) — поиск, bulk delete
  - **Категории** (`/admin/categories`) — поиск, фильтр по типу, bulk delete
  - **Медиа** (`/admin/media`) — поиск, фильтры по диску/коллекции, bulk delete
  - **Страницы** (`/admin/pages`) — RichEditor, SEO-поля, фильтр по публикации
  - **Услуги** (`/admin/services`) — RichEditor, цена, SEO, фильтры по категории/публикации
  - **Проекты** (`/admin/projects`) — типы/статусы съёмок, фильтры
  - **Альбомы** (`/admin/albums`) — featured, published, счётчик фото
  - **Фотографии** (`/admin/photos`) — связь с альбомом и медиа
  - **Блог** (`/admin/posts`) — RichEditor, SEO, категории, дата публикации
  - **Отзывы** (`/admin/testimonials`) — клиент, контент, сортировка
  - **Заявки** (`/admin/inquiries`) — статусы, услуга, связанный пользователь
- Навигация сгруппирована: **Контент**, **Администрирование**, **Заявки**
- Все ресурсы поддерживают: поиск, фильтры, bulk actions

## 2026-06-25 — Media UX: Альбомы, загрузка, превью

### Изменения БД
- **Новая миграция**: `modify_albums_add_type_and_make_project_id_nullable`
- `albums.project_id` теперь NULL (альбом не обязан быть в проекте)
- Добавлено поле `albums.type` (portfolio/project/homepage/service/client)

### Модели
- `Album`: добавлен `type`, `project()` теперь nullable BelongsTo
- `Media`: подключён `MediaObserver` для авто-заполнения метаданных

### MediaObserver
- Автоматическое определение: `mime_type`, `width`, `height`, `file_size`
- Автоматическая генерация WebP-превью (400px) при загрузке изображения

### Filament: MediaResource
- Из формы удалены технические поля: disk, thumbnail_path, mime_type, width, height, file_size
- Оставлены только: title, alt_text, file_path (FileUpload), collection
- Из таблицы скрыты disk, mime_type, file_size (доступны по toggle)

### Filament: AlbumResource
- В форму добавлено поле `type` (с условным показом `project_id` для type=project)
- В таблицу добавлена колонка `type` (badge с цветом), сортировка, фильтр
- Добавлена новая страница **«Загрузить фотографии»** (`/admin/albums/upload`)
  - Drag & drop, множественный выбор (до 500 файлов)
  - Поля альбома: название, тип, описание, обложка
  - Для type=project показывается выбор проекта
  - Массовое создание: Album → Media (с метаданными/превью) → Photo
- Добавлен **RelationManager** фотографий на странице редактирования альбома
  - Сетка превью (80px), подпись, порядок сортировки
  - Редактирование, удаление, перетаскивание для сортировки
- Добавлена кнопка «Загрузить фотографии» на странице списка альбомов

## 2026-06-25 — Тесты: Unit + Feature

### Модели
- User реализует `FilamentUser` с `canAccessPanel()` (доступ в админку для авторизованных)

### Фабрики
- Созданы фабрики для всех 11 моделей (Role, Category, Media, Page, Service, Project, Album, Photo, Post, Testimonial, Inquiry)
- Обновлена UserFactory (добавлены phone, status)

### Unit-тесты
- `tests/Unit/Models/ModelRelationshipsTest.php` — 22 теста всех связей моделей (BelongsTo, HasMany, BelongsToMany, nullable)
- `tests/Unit/Observers/MediaObserverTest.php` — 6 тестов MediaObserver:
  - Заполнение метаданных (mime_type, width, height, file_size)
  - Генерация WebP-превью (400px) для landscape и portrait
  - Отсутствие превью для non-image файлов
  - Отсутствие ошибок для отсутствующего файла

### Feature-тесты
- `tests/Feature/UploadPhotosTest.php` — 5 тестов логики массовой загрузки:
  - Рендеринг страницы загрузки
  - Создание альбома + Media + Photo
  - Работа без обложки
  - Привязка альбома к проекту (type=project)
  - null project_id для type=client

## 2026-06-25 — Исправления проблем

### Критические
- **Media.php**: удалён сломанный метод `mediaables()` — класс `Mediaable` не существует.
- **UserForm.php**: `Toggle` для `status` заменён на `Select` — Toggle возвращает boolean, а БД ожидает enum('active', 'inactive').

### Высокие/Средние
- **UsersTable.php**: `IconColumn::make('status')->boolean()` заменён на `TextColumn` с badge и цветом (success/danger) — 'inactive' как непустая строка всегда была truthy.
- **Новая миграция**: `add_indexes_to_users_table` — добавлены индексы для `phone` и `status`.

### Низкие
- **CreateAlbum Action**: логика создания альбома вынесена в `app/Actions/Album/CreateAlbum.php` — теперь тестируется Action, а не дублируется логика.
- **UploadPhotos.php**: добавлен явный `$this->form->validate()` перед `getState()`.

## 2026-06-25 — Этап 3.1: Главная страница

### Выполнено
- Создан базовый Blade Layout (`resources/views/layouts/site.blade.php`):
  - HTML5-разметка, title, meta description, Vite, Header/Footer
- Созданы Blade-компоненты:
  - `resources/views/components/site/header.blade.php` — логотип, меню (Главная, Услуги, Портфолио, Блог), кнопка заявки
  - `resources/views/components/site/footer.blade.php` — копирайт, телефон, email, ссылка на политику конфиденциальности
- Контактные данные вынесены в `config/contacts.php` (подгружаются из .env)
- Создан **HomeController** (`app/Http/Controllers/HomeController.php`):
  - Все выборки из БД выполняются в контроллере, Blade получает готовые коллекции
  - Eager loading для cover/photo
  - Метод `storeInquiry()` — валидация и создание записи в inquiries
- Создана главная страница (`resources/views/home.blade.php`):
  - **Hero** — заголовок, описание, кнопки (статика)
  - **Услуги** — опубликованные, сортировка по sort_order, обложка, цена
  - **Избранные работы** — albums (type=portfolio, is_featured=true, is_published=true)
  - **Отзывы** — опубликованные, с фото клиента
  - **Последние статьи** — 3 последних опубликованных поста, обложка, дата
  - **Форма заявки** — имя, телефон, услуга (select), комментарий; POST → inquiries
- Маршруты: `GET /` (HomeController), `POST /inquiry` (storeInquiry)
- CSS: добавлен `@source '../views'` в `app.css` для Tailwind-сканирования Blade-шаблонов

## 2026-06-26 — Соглашение о персональных данных

### Миграции
- **Новая миграция**: `add_agreed_to_terms_to_inquiries_table`
- Добавлено поле `inquiries.agreed_to_terms` (boolean, default false)

### Модели
- `Inquiry`: `agreed_to_terms` добавлен в `$fillable`

### Filament: InquiryForm
- Добавлен чекбокс `agreed_to_terms` (required)

### Публичные формы
- Чекбокс «Согласен на обработку персональных данных» добавлен во все три формы:
  - Главная страница (`home.blade.php`)
  - Список услуг (`services/index.blade.php`)
  - Детальная страница услуги (`services/show.blade.php`)
- Валидация: `required|accepted` в `HomeController::storeInquiry()`

---

## 2026-06-26 — Улучшение работы с фотоальбомами

### Миграции
- **Новые таблицы**: `page_album`, `post_album` (pivot many-to-many)

### Модели
- **Album**: добавлены отношения `pages()` и `posts()` (BelongsToMany)
- **Page**: добавлено отношение `albums()` (BelongsToMany)
- **Post**: добавлено отношение `albums()` (BelongsToMany)

### Auto-slug для Album
- При вводе `title` slug автоматически заполняется (транслитерация через `Str::slug`)
- Если пользователь вручную изменил slug — автогенерация прекращается (отслеживается через скрытое поле `_slug_manual`)
- Для существующих записей slug не перегенерируется (флаг `_slug_manual` выставляется при загрузке формы)

### Дозагрузка фотографий
- На странице редактирования альбома (`EditAlbum`) добавлена кнопка «Добавить фотографии»
- Новые файлы добавляются к существующим (сортировка продолжается с `max(sort_order) + 1`)
- Используется существующий механизм хранения (Media + Photo)

### Управление фотографиями
- В `PhotosRelationManager` добавлено действие «Сделать обложкой» (обновляет `albums.cover_media_id`)
- Действие доступно как на строке, так и в шапке таблицы
- Удаление, изменение порядка (reorderable), редактирование подписи — уже поддерживались

### Many-to-many: Page + Post ↔ Album
- Созданы pivot-таблицы `page_album` и `post_album` (FK + CASCADE)
- Элегантные отношения добавлены во все три модели
- Существующие данные не затронуты (обратная совместимость)

### Filament: PageForm / PostForm
- Добавлена секция «Альбомы» с `Select(multiple)` для привязки альбомов
- Поддерживается поиск и предзагрузка (preload)
- Cover Image сохранён без изменений

### Документация
- Обновлены `database.md` и `changelog.md`

---

## 2026-06-26 — Этап 2.3: Реализация доступа и ролей пользователя

### Изменения БД
- **Новая миграция**: `add_is_system_to_roles_table`
- Добавлено поле `roles.is_system` (boolean, default true) + индекс

### Модели
- **User**: метод `canAccessPanel()` теперь требует `status = active` И роль `admin`
- **User**: добавлены методы `isAdmin()`, `hasRole()`, `hasAnyRole()`, `hasAllRoles()`
- **Role**: добавлен `casts` для `is_system` (boolean)
- **Role**: защита от удаления и изменения slug у системных ролей (LogicException)
- **Role**: в `$fillable` добавлено `is_system`

### Команды
- Создана `MakeFilamentUserCommand` — `php artisan make:filament-user` создаёт админа с ролью admin

### Filament: RoleResource
- **RoleForm**: slug disabled для системных ролей
- **RolesTable**: добавлена колонка `is_system` (badge)
- **EditRole**: кнопка удаления скрыта для системных ролей
- **RolesTable**: bulk delete скрыт

### Пользовательский кабинет
- Создан `CabinetController` с `index()`
- Создан `resources/views/cabinet/index.blade.php` — приветствие
- Маршрут `GET /cabinet` защищён middleware `auth`

### Аутентификация
- Создан `Auth\LoginController` (create, store, destroy)
- Создана страница входа `resources/views/auth/login.blade.php`
- Созданы маршруты в `routes/auth.php`: login, logout
- Подготовлена структура для подключения Breeze

### Шапка сайта
- Для гостя: Главная, Услуги, Портфолио, Блог, Оставить заявку, Войти
- Для авторизованного: добавлен «Личный кабинет», убран «Войти»
- Для администратора: дополнительно «Админка» (ссылка на /admin)

### Тесты
- `tests/Feature/Auth/AccessTest.php` — 7 тестов: доступ ролей к панели, редирект гостя, доступ в кабинет
- `tests/Feature/Auth/RoleMethodsTest.php` — 8 тестов: isAdmin, hasRole, hasAnyRole, hasAllRoles, inactive user
- `tests/Feature/Auth/SystemRolesTest.php` — 4 теста: защита удаления, защита slug, создание кастомной роли, seeded roles
- Исправлен `UploadPhotosTest` — тестовый пользователь теперь получает роль admin

### Документация
- Обновлены `database.md` (описание is_system, системы ролей и доступа)
- Обновлена `architecture.md` (система ролей, доступ, кабинет, аутентификация)

---

## 2026-06-25 — Этап 3.2: Страница услуг

### Выполнено
- Создан **ServiceController** (`app/Http/Controllers/ServiceController.php`):
  - `index()` — все опубликованные услуги, сгруппированные по категориям
  - `show($slug)` — детальная страница услуги с формой заявки
  - Eager loading для cover/category, выборка только нужных полей
- Создана страница списка услуг (`resources/views/services/index.blade.php`):
  - Группировка по категориям с заголовками
  - Карточки с обложкой, кратким описанием и ценой
  - Форма заявки внизу (без привязки к конкретной услуге)
- Создана детальная страница услуги (`resources/views/services/show.blade.php`):
  - Хлебные крошки (Главная / Услуги / Категория)
  - Обложка, заголовок, цена, описание (RichEditor — `prose`)
  - Форма заявки с предвыбранной услугой (hidden service_id)
  - Блок «Другие услуги»
- Заменены маршруты-редиректы на реальные контроллеры в `routes/web.php`
- Создан **CategorySeeder** с 7 категориями услуг согласно roadmap
- Исправлен Vite-баг в тестах: `@vite` теперь загружается только при наличии manifest или hot файла

---

## 2026-06-29 — Исправление критических ошибок

### Критические
- **MediaObserver**: добавлена принудительная установка `$media->disk = $media->disk ?? 'public'` — колонка `disk` в БД `NOT NULL`, но форма MediaResource её не содержит. Observer теперь гарантирует, что `disk` никогда не будет null.
- **PhotosRelationManager**: удалён `DetachAction` из headerActions — он использовался на `HasMany`-связи `photos`, а `DetachAction` вызывает `$relationship->detach()`, доступный только для `BelongsToMany`. Это приводило бы к `BadMethodCallException`.

### Прочее
- Проведён полный аудит проекта: 55 тестов пройдено, Pint чистый.
- Все маршруты, контроллеры, модели, миграции, Filament-ресурсы и Blade-шаблоны проверены на соответствие и целостность.

---

## 2026-06-29 — Этап 3.3: Страница портфолио

### Выполнено
- Создан **PortfolioController** (`app/Http/Controllers/PortfolioController.php`):
  - `index()` — все опубликованные альбомы типа `portfolio`, сортировка по `sort_order`, eager loading cover
  - `show($slug)` — детальная страница альбома с фото (сортировка по `sort_order`)
- Создана страница портфолио (`resources/views/portfolio/index.blade.php`):
  - Hero-секция с заголовком и описанием
  - Адаптивная CSS-сетка (1/2/3 колонки) с masonry-эффектом
  - Анимация появления (fadeInUp) с задержкой для каждой карточки
  - Карточки: обложка (или placeholder), ховер с градиентом, заголовком и описанием
  - Пустое состояние
- Создана детальная страница альбома (`resources/views/portfolio/show.blade.php`):
  - Хлебные крошки (Главная / Портфолио / Альбом)
  - Заголовок и описание альбома
  - Сетка фотографий (1/2/3 колонки)
  - Пустое состояние
- Заменены маршруты-редиректы на реальный контроллер в `routes/web.php`
- Lightbox для полноэкранного просмотра фото (vanilla JS, без зависимостей)

---

## 2026-06-29 — Редизайн /services + service_items + albums ↔ services

### Изменения БД
- **Новая миграция**: `create_service_items_table` (первая версия)
  - `service_id` (FK → services, CASCADE) — позже удалено
  - `label`, `is_included`, `sort_order`
- **Новая миграция**: `add_price_note_to_services`
  - `services.price_note` (TEXT, nullable)
- **Новая миграция**: `create_service_service_item_table` — pivot для many-to-many
  - `service_id`, `service_item_id`, `is_included`, `sort_order`
- **Новая миграция**: `drop_service_id_from_service_items` — `service_items` становится мастер-справочником
- **Новая миграция**: `create_album_service_table` — pivot для many-to-many услуг ↔ альбомов

### Модели
- **ServiceItem** — мастер-справочник (без FK), `BelongsToMany services()`
- **Service**
  - `items()` → BelongsToMany `ServiceItem` (с pivot `is_included`, `sort_order`)
  - `albums()` → BelongsToMany `Album`
- **Album** — добавлена `services()` BelongsToMany

### Filament
- **ServiceItemResource** — новый CRUD-ресурс для управления мастер-списком пунктов
- **ServiceForm** — `items` теперь `Select(multiple)` с `createOptionForm` (выбор существующих + создание новых)
- **ServiceForm** — добавлен `albums` `Select(multiple)` для привязки альбомов-примеров
- **ServicesTable** — `items_count` через `counts('items')`

### Публичные страницы
- **services/index.blade.php** — полный редизайн (предыдущий этап), адаптация под BelongsToMany (pivot)
- **services/show.blade.php** — добавлен блок «Примеры работ» — сетка альбомов-примеров с превью и ссылкой на портфолио

### Тесты
- Обновлены тесты: `service_belongs_to_many_items`, `service_item_belongs_to_many_services`, `service_item_casts_is_included_to_boolean`
- Добавлен тест: `service_belongs_to_many_albums`
- Всего 59 тестов, 106 assertions

### Документация
- Обновлены `database.md` (pivot-таблицы, service_items как справочник)
- Обновлён `changelog.md`

---
