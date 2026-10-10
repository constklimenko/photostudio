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

### Медиа — перегенерация кэша производных изображений (в фоне)

Команда `media:regenerate-image-cache` пересоздаёт кэш производных изображений
(display — WebP ≤800px, lightbox — PNG ≤1600px) на диске `image_cache`.
Тяжёлая работа выполняется **в фоновом режиме через очередь**: команда только
отбирает Media и ставит по одному Job `RegenerateMediaImageCache` на запись,
после чего сразу завершается. Регенерацию выполняет queue worker
(`php artisan queue:work`).

Кэш генерируется и лениво при обращении к URL, поэтому команда нужна для
предварительного «прогрева» — например, после массовой загрузки фотографий,
сбоев, изменения формата или лимитов:

```bash
# Показать, что будет поставлено в очередь, без диспатча
php artisan media:regenerate-image-cache --dry-run

# Поставить в очередь только Media без хотя бы одного варианта кэша
php artisan media:regenerate-image-cache

# Пересоздать кэш у всех изображений (включая существующие варианты)
php artisan media:regenerate-image-cache --force

# Ограничить количество записей за запуск
php artisan media:regenerate-image-cache --limit=50

# Один конкретный Media по ID
php artisan media:regenerate-image-cache --id=123
```

**Что делает:**
- Отбирает Media типа `image/*` с `file_path`
- По умолчанию — только записи, у которых отсутствует хотя бы один
  кэш-вариант (`display` и/или `lightbox`); с `--force` — все изображения
- Диспатчит по одному Job `RegenerateMediaImageCache` на запись (retry 3×
  с backoff, timeout 180 c) — обработка идёт асинхронно
- Job читает оригинал через стрим с диска `Media::disk` и пересоздаёт
  только кэш-варианты, **не трогая** метаданные и WebP-thumbnail
  (для полной регенерации производных — `media:regenerate-thumbnails --force`)
- Повторный запуск безопасен (идемпотентность); сбой одного Media не мешает остальным

### Медиа — миграция локальных оригиналов на Яндекс.Диск

Команда `media:migrate-to-yandex` переводит локальные оригиналы изображений
на Яндекс.Диск по безопасной последовательности:
`upload → verify → update Media → delete local original`.

```bash
# Dry-run — показать план миграции, ничего не менять
php artisan media:migrate-to-yandex --dry-run

# Ограничить число миграций за запуск
php artisan media:migrate-to-yandex --limit=10

# Мигрировать один конкретный Media по ID
php artisan media:migrate-to-yandex --media-id=123

# Комбинация: сначала посмотреть план для одной записи
php artisan media:migrate-to-yandex --dry-run --media-id=123
```

**Что делает:**
- Кандидаты — только изображения (`image/*`) на локальных дисках; пропускает
  записи уже на Яндекс.Диске и других remote-дисках, производные
  (диски `thumbnails`, `image_cache`), чужие/неизвестные storage,
  записи без пути/MIME/локального файла — с указанием причины
- Загружает оригинал стримом во временную копию на Диске
- Проверяет удалённый файл: наличие → размер → sha256 содержимого
- Обновляет `Media.disk = yandex_disk` только после успешной проверки
- Удаляет локальный оригинал только после обновления БД

**Гарантии:**
- Идемпотентность: повторный запуск ничего не ломает — записи на Диске
  пропускаются; существующий remote-файл при совпадении размера и хеша
  переиспользуется, при расхождении — ошибка конфликта (файл не перезаписывается)
- Любой сбой оставляет локальный оригинал на месте; сценарий
  «удалить локально → потом загрузить» невозможен
- Ошибка одного файла не останавливает batch; итог показывает статистику:
  обработано / мигрировано / пропущено / с ошибками / локально удалено
- После миграции удаление такой Media идёт по политике B6: файл на Диске
  удаляется только по явному решению пользователя

### Медиа — очистка кэша производных изображений

Команда `media:prune-image-cache` управляет локальным кэшем display (≤800px) /
lightbox (≤1600px) на диске `image_cache`.

```bash
# Показать размер кэша и лимит
php artisan media:prune-image-cache --stats

# Вытеснить самые старые файлы до лимита (IMAGE_CACHE_MAX_MB)
php artisan media:prune-image-cache

# Полностью очистить кэш (варианты перегенерируются лениво)
php artisan media:prune-image-cache --all
```

### Медиа — проверка подключения к диску

Команда `media:test-storage` выполняет полный цикл записи/чтения/удаления
тестового файла — проверка соединения и прав.

```bash
# Проверить Яндекс.Диск (по умолчанию)
php artisan media:test-storage

# Проверить другой диск
php artisan media:test-storage --disk=public
```

### Медиа — проверка целостности

Команда `media:check` проверяет целостность Media Storage и обнаруживает
potential orphan-файлы на Яндекс.Диске. **Ничего не удаляет по умолчанию.**

```bash
# Проверить все Media
php artisan media:check

# Проверить конкретный Media по ID
php artisan media:check --media-id=123

# Ограничить число проверяемых записей
php artisan media:check --limit=100

# Восстановить отсутствующие thumbnails
php artisan media:check --fix-thumbnails
```

**Что проверяет:**
- DB → Storage: оригинал существует на диске; thumbnail на диске `thumbnails`;
  кэш display/lightbox; metadata (file_size, dimensions, file_size vs disk)
- Yandex → DB: файлы на Яндекс.Диске без соответствующей записи Media —
  **potential orphan files** (не ошибки — пользователь мог сознательно
  оставить файл при удалении Media)

**Итоговый отчёт:**
```
Checked: 1000
OK: 950
Missing original: 10
Missing thumbnail: 20
Metadata mismatch: 5
Potential orphan Yandex files: 15
Errors: 0
```

### Бэкап и перенос базы данных

Перенос базы между копиями сайта без сторонних пакетов: используются штатные
бинарники `mysqldump`/`mysql` (MySQL/MariaDB), для SQLite — копия файла.

```bash
# Создать полный бэкап (структура + данные) в storage/app/backups/{db}-{дата}.sql.gz
php artisan db:backup

# Восстановить из конкретного файла (.sql / .sql.gz)
php artisan db:restore storage/app/backups/fotoskazka-2026-09-19_081852.sql.gz

# Восстановить последний бэкап (подтверждение перед заменой; --force — без него)
php artisan db:restore
```

**Перенос между копиями:**

```bash
# Копия А — создать бэкап
php artisan db:backup

# Скопировать файл из storage/app/backups/ на копию Б

# Копия Б — восстановить
php artisan db:restore
```

**Опции `db:backup`:**
- `--path=` — свой путь для файла бэкапа;
- `--database=` — соединение из `config/database.php` (по умолчанию — `DB_CONNECTION`);
- `--no-compress` — не сжимать через gzip (файл `.sql`);
- `--prune=N` — хранить только N последних бэкапов в стандартной директории
  (по умолчанию 7, `--prune=0` — без очистки).

**Опции `db:restore`:**
- аргумент `dump` — путь к файлу либо имя файла из стандартной директории;
  без аргумента — последний бэкап или интерактивный выбор из списка;
- `--database=` — целевое соединение;
- `--force` — пропустить подтверждение (данные целевой БД заменяются полностью).

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

## Сборка и развёртывание (Docker)

Прод-развёртывание идёт через Docker Compose и **не зависит от версии PHP на
хосте** (на сервере может стоять PHP 7.4). Стек: `web` (nginx), `app`
(php-fpm 8.4), `db` (MySQL 8.0), `queue` (`queue:work`), `scheduler`
(`schedule:work`). Ассеты собираются отдельным сервисом `node`.

### Состав

| Файл | Назначение |
|---|---|
| `docker-compose.yml` | сервисы, порты, тома, переменные |
| `docker/php/Dockerfile` | `php:8.4-fpm` + расширения (`gd` с WebP, `intl`, `zip`, `bcmath`, `exif`, `opcache`, `pcntl`, `pdo_mysql`, `pdo_sqlite`), `default-mysql-client` для `db:backup`/`db:restore` |
| `docker/php/php.ini` | memory 512M, upload 200M, opcache |
| `docker/php/entrypoint.sh` | права на `storage`, `storage:link`, для `app` — `migrate --force` и `optimize` |
| `docker/nginx/generate-config.sh` | генерирует конфиг nginx при старте (HTTP + HTTPS, если есть сертификат) |
| `.env.docker.example` | шаблон прод-окружения |

### Первый запуск

```bash
cp .env.docker.example .env
# заполнить APP_URL, DB_PASSWORD, DB_ROOT_PASSWORD, YANDEX_DISK_TOKEN и т.д.
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env

docker compose build
docker compose up -d db                       # дождаться статуса healthy
docker compose run --rm -e RUN_SETUP=false app composer install --no-dev --optimize-autoloader
docker compose run --rm node                  # сборка public/build (npm ci && npm run build)
docker compose up -d                          # app выполнит migrate + optimize
docker compose exec app php artisan db:seed --force   # роли, категории
```

Проверка: `docker compose ps` и `curl -I http://localhost`.

### Обновление кода

```bash
git pull
docker compose run --rm -e RUN_SETUP=false app composer install --no-dev --optimize-autoloader
docker compose run --rm node
docker compose up -d --build
```

> `opcache.validate_timestamps=0`, поэтому после деплоя контейнеры обязательно
> пересоздаются (`up -d --build`), иначе PHP продолжит отдавать старый код.

### HTTPS (Let's Encrypt)

Конфиг nginx генерируется скриптом `docker/nginx/generate-config.sh`: пока
сертификата нет — только HTTP и ACME-challenge; после выпуска — добавляется
HTTPS-блок (443). Сертификаты — `/etc/letsencrypt`, webroot — `/var/www/certbot`
(оба монтируются в `web`).

```bash
apt install -y certbot
mkdir -p /var/www/certbot
docker compose up -d --force-recreate web

certbot certonly --webroot -w /var/www/certbot \
  -d example.com -d www.example.com --agree-tos --no-eff-email

docker compose up -d --force-recreate web     # перечитать конфиг с HTTPS
```

Автообновление (таймер `certbot` на хосте) + перезагрузка nginx после продления:

```bash
mkdir -p /etc/letsencrypt/renewal-hooks/deploy
cat > /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh <<'EOF'
#!/bin/sh
docker compose -f /var/www/fotoskazka/docker-compose.yml exec -T web nginx -s reload
EOF
chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
certbot renew --dry-run
```

### Полезные команды

```bash
docker compose ps                       # статус сервисов
docker compose logs -f app              # логи php-fpm
docker compose exec app php artisan ... # artisan внутри app
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:backup
docker compose exec app php artisan db:restore <файл> --force
docker compose restart queue            # после деплоя — queue:restart
```

### Особенности окружения

- **MTU.** Если хост-интерфейс имеет MTU меньше 1500 (типично для VPS с
  туннелем), задайте его для Docker в `/etc/docker/daemon.json`
  (`{"mtu": 1450}`) и перезапустите `docker` — иначе крупные загрузки в
  контейнерах отваливаются по таймауту.
- **Память.** При 1 ГБ RAM добавьте swap (2 ГБ), php-fpm настроен на
  `pm=ondemand`, MySQL ограничен `--innodb-buffer-pool-size=128M`.

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
