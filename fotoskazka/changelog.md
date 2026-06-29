# Changelog



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
