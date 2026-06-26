# Архитектура проекта

## Стек

| Компонент     | Версия        |
|---------------|---------------|
| PHP           | 8.4           |
| Laravel       | 13.16         |
| MySQL         | 8.x           |
| Filament      | 4.11          |
| Livewire      | 3.8           |
| Node          | 20+           |
| Vite          | 6.x           |

## Структура приложения

```
app/
├── Actions/                  # Action-классы (бизнес-логика)
│   └── Album/
│       └── CreateAlbum.php   # Создание альбома с медиа и фото
├── Filament/
│   ├── Resources/            # Filament ресурсы (CRUD)
│   │   ├── Albums/
│   │   │   ├── AlbumResource.php
│   │   │   ├── Pages/
│   │   │   │   └── UploadPhotos.php   # Drag&drop загрузка
│   │   │   ├── RelationManagers/
│   │   │   │   └── PhotosRelationManager.php
│   │   │   └── Schemas/
│   │   │       └── AlbumForm.php
│   │   ├── Categories/
│   │   ├── Inquiries/
│   │   ├── Media/
│   │   │   └── Schemas/
│   │   │       └── MediaForm.php
│   │   ├── Pages/
│   │   ├── Photos/
│   │   ├── Posts/
│   │   ├── Projects/
│   │   ├── Roles/
│   │   ├── Services/
│   │   ├── Testimonials/
│   │   └── Users/
│   │       ├── Schemas/
│   │       │   └── UserForm.php
│   │       └── Tables/
│   │           └── UsersTable.php
│   └── Resources/Users/UserResource.php
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Models/                   # Eloquent модели (12 шт.)
│   ├── Album.php
│   ├── Category.php
│   ├── Inquiry.php
│   ├── Media.php
│   ├── Page.php
│   ├── Photo.php
│   ├── Post.php
│   ├── Project.php
│   ├── Role.php
│   ├── Service.php
│   ├── Testimonial.php
│   └── User.php
├── Observers/
│   └── MediaObserver.php     # Авто-метаданные + WebP превью
└── Providers/
    └── Filament/
        └── AdminPanelProvider.php
```

## Шаблоны

### Action-классы (`app/Actions/`)

Инкапсулируют бизнес-логику, вынесенную из контроллеров / Filament-страниц.
Позволяют:
- тестировать логику независимо;
- переиспользовать в разных точках входа (HTTP, CLI, API).

Пример: `CreateAlbum` — транзакция создания альбома с медиа и фото.

### Наблюдатели (`app/Observers/`)

`MediaObserver` — автоматически заполняет mime_type, width, height, file_size
и генерирует WebP-превью (400px) при создании Media.

### Filament Resources

Для каждой сущности создан Resource с:
- Form (Schema)
- Table (колонки, фильтры, bulk-actions)
- Поиск

Страницы (Pages) могут дополнять стандартный CRUD:
- `UploadPhotos` — массовая загрузка фото в альбом

### Вынесенные Schema/Table

Для Users ресурса форма и таблица вынесены в отдельные классы:
- `Schemas/UserForm.php`
- `Tables/UsersTable.php`

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
- Развитие кабинета — в следующих этапах

### Аутентификация

- Стандартная Laravel-аутентификация (сессии)
- `POST /login`, `POST /logout`, `GET /login`
- `LoginController` с валидацией и редиректом в кабинет
- Структура маршрутов готова к подключению Breeze

## Frontend

- **Blade** — серверный рендеринг
- **Vite** — сборка JS/CSS
- Филаментовские компоненты (Livewire) для админ-панели

Публичная часть (главная, услуги, портфолио, блог) будет на Blade.

## Хранение файлов

```
storage/app/public/
├── images/          # Оригиналы изображений
├── thumbnails/      # WebP превью (400px)
└── ...              # Прочие файлы
```

- Laravel Filesystem
- Диск `public` (локальный)
- MediaObserver генерирует превью в `thumbnails/`
- Storage symlink: `public/storage -> storage/app/public`

### Будущее
- Перенос оригиналов на Яндекс.Диск
- Локальный кэш превью
- Абстракция через драйверы Laravel Filesystem

## База данных

См. `database.md` — 14 бизнес-таблиц + служебные (cache, jobs, sessions).

## Тестирование

| Уровень  | Расположение                       | Запуск          |
|----------|------------------------------------|-----------------|
| Unit     | `tests/Unit/Models/`               | `php artisan test` |
| Unit     | `tests/Unit/Observers/`            | `php artisan test` |
| Feature  | `tests/Feature/`                   | `php artisan test` |

- БД: SQLite in-memory
- RefreshDatabase для тестов, изменяющих БД
- 35 тестов, 67 утверждений

## Ключевые решения

### Album.type (вместо отдельных таблиц)
Введено поле `type` (VARCHAR 20) вместо создания отдельных таблиц
для портфолио, клиентских галерей, слайдеров и т.д.
Простое и гибкое решение без избыточной нормализации.

### MediaObserver вместо ручного заполнения
Автоматическое определение mime_type, размеров и генерация превью
при создании Media — исключает ошибки и дублирование кода.

### Action-классы вместо логики в Pages
Бизнес-логика создания альбома вынесена в `CreateAlbum` Action
для тестируемости и переиспользования.

## Правила разработки

- strict_types рекомендуется
- PSR-12 (Laravel Pint)
- Eloquent Relationships (belongsTo, hasMany, belongsToMany)
- Избегать JSON-полей для связанных данных
- Не добавлять зависимости без необходимости
- Обновлять `database.md` при изменении схемы БД
- Обновлять `architecture.md` при изменении архитектуры
- Обновлять `changelog.md` после каждой задачи
