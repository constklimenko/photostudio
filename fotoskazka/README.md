<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Тесты

Проект использует **PHPUnit 12** (Laravel 13). Тесты запускаются на отдельной in-memory SQLite базе (`:memory:`) — реальная БД не затрагивается.

### Команды

```bash
# Все тесты
php artisan test            # или composer test

# Конкретный файл
php artisan test tests/Feature/Http/Controllers/VideoControllerTest.php

# Конкретный метод
php artisan test --filter=test_embed_url_converts_youtube_watch_url

# Отчёт о покрытии (нужен pcov/xdebug)
php artisan test --coverage-text
```

### Медиа — перегенерация превью

Команда `media:regenerate-thumbnails` регенерирует WebP-превью (400px) для записей в таблице `media`.

```bash
# Dry-run — показать что будет обработано без изменений
php artisan media:regenerate-thumbnails --dry-run

# Регенерировать все (в т.ч. корректные превью)
php artisan media:regenerate-thumbnails --force

# Ограничить количество
php artisan media:regenerate-thumbnails --limit=50

# Один конкретный Media по ID
php artisan media:regenerate-thumbnails --id=123
```

**Что делает:**
- Находит Media с `file_path` и `mime_type` начинающимся с `image/`
- По умолчанию обрабатывает записи, у которых:
  - нет превью (`thumbnail_path` пуст);
  - путь содержит ошибки (`thumbnails/thumbnails/`, `/./`);
  - путь корректный, но файла нет на диске `thumbnails` — файл создаётся заново из оригинала.
- С `--force` — все изображения, даже с существующими превью
- В `--dry-run` показывает причину обработки (`no thumbnail`, `broken path`, `file missing`)
- Читает оригинал через стрим с диска `Media::disk` (не использует `path()`)
- Пишет WebP-превью на диск `thumbnails` (локальный кэш)
- Обновляет `Media::thumbnail_path` (без лишних папок `thumbnails/`)

### Структура

Тесты делятся на **Unit** (изолированная логика) и **Feature** (HTTP-слой, маршруты, взаимодействие с БД).

| Директория | Описание | Примеры |
|---|---|---|
| `tests/Unit/Models/` | Логика моделей и связей | `VideoModelTest` — accessors `embed_url`, `is_upload`, `source_url`, casts; `ModelRelationshipsTest` — все связи |
| `tests/Unit/Observers/` | Элоquent-обсерверы | `PageObserverTest` — инвалидация кэша; `MediaObserverTest` — метаданные и WebP-превью |
| `tests/Unit/Mail/` | Mailable-классы | `NewInquiryMailTest` — subject, шаблон, содержимое письма |
| `tests/Feature/Http/Controllers/` | Публичные контроллеры | `HomeControllerTest`, `ServiceControllerTest`, `PortfolioControllerTest`, `VideoControllerTest` |
| `tests/Feature/Auth/` | Доступ и роли | `AccessTest`, `RoleMethodsTest`, `SystemRolesTest` |
| `tests/Feature/Console/` | Artisan-команды | `MakeFilamentUserCommandTest` |
| `tests/Feature/` | Бизнес-сценарии | `InquiryTest` (заявки, уведомления), `UploadPhotosTest` (массовая загрузка) |

### Соглашения

- Базовый класс — `Tests\TestCase`; тесты с БД используют `RefreshDatabase`.
- Тесты контроллеров — под `tests/Feature/Http/Controllers/`, названия методов `test_*_*`.
- Фабрики моделей лежат в `database/factories/` (`Video::factory()->create()`).
- После изменений: `composer test` + `./vendor/bin/pint`.

### Покрытие

Публичная часть (контроллеры, модели, сервисы, actions, jobs, команды, mail, observers) покрыта тестами полностью или почти полностью. Админ-панель Filament (Resources/Forms/Tables) тестами не покрыта.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
