# Changelog

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
