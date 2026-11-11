# План обновления MySQL 8.0 → 8.4 LTS

## Зачем

- MySQL 8.0 — EOL с апреля 2026, обновлений безопасности больше нет.
- MySQL 8.4 LTS — премьер-поддержка до апреля 2029, расширенная — до 2032.
- Laravel 13 и текущая схема БД с 8.4 совместимы; критичных изменений синтаксиса в проекте нет.

## Текущее состояние (прод)

- Образ: `mysql:8.0` (в контейнере `fotoskazka-db-1`), версия 8.0.46.
- Данные: volume `dbdata`, база `fotoskazka`, размер ~318 МБ.
- Подключение: `db:3306` внутри docker-сети, наружу порт не публикуется.
- Бэкапы: `storage/app/backups/*.sql.gz` (ручные, на том же диске).

## Предварительные проверки

1. Снять полный дамп с совместимыми опциями:

   ```bash
   docker compose exec -T db sh -c \
     'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" \
       --single-transaction --quick --routines --triggers --events \
       --default-character-set=utf8mb4 fotoskazka' \
     | gzip > /root/fotoskazka-pre-8.4-$(date +%F).sql.gz
   ```

2. Проверить восстановление дампа на отдельной базе/контейнере (staging) — обязательно.
3. Проверить кодировку и collation: `SHOW VARIABLES LIKE 'character_set_server'` и `SHOW TABLE STATUS` (ожидается `utf8mb4`/`utf8mb4_unicode_ci`).
4. Убедиться, что приложение не использует `mysql_native_password` (в 8.4 он отключён по умолчанию). Пользователь создаётся образом на `caching_sha2_password`, но проверить:

   ```sql
   SELECT user, host, plugin FROM mysql.user;
   ```

5. Убедиться, что на диске есть минимум 2–3 ГБ свободного места (дамп + распаковка + запас).
6. Прогнать `php artisan test` на ветке с целевой версией зависимостей.

## Вариант A (рекомендуемый): dump → restore в новый контейнер 8.4

Даёт чистую установку и простой откат: старый volume остаётся нетронутым.

1. Включить режим обслуживания и остановить обработчики:

   ```bash
   php artisan down
   docker compose stop queue scheduler
   ```

2. Сделать дамп (см. выше) и скопировать его вне сервера.
3. Запустить новый MySQL 8.4 на **новом** volume:

   ```yaml
   db:
     image: mysql:8.4
     volumes:
       - dbdata84:/var/lib/mysql
   ```

   ```bash
   docker compose up -d db
   ```

4. Импортировать дамп:

   ```bash
   gunzip -c /root/fotoskazka-pre-8.4.sql.gz | \
     docker compose exec -T db sh -c \
       'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" fotoskazka'
   ```

5. Поднять приложение, выполнить миграции (если есть) и прогреть кэши:

   ```bash
   php artisan migrate --force
   php artisan optimize
   docker compose up -d queue scheduler
   php artisan up
   ```

6. Проверка:
   - `SELECT VERSION();` → 8.4.x;
   - главная, услуги, портфолио, блог — 200;
   - вход в админку, список заявок, загрузка фото;
   - тестовая заявка с сайта доходит на email;
   - `php artisan queue:work --once` без ошибок.

7. Откат: вернуть `mysql:8.0` и старый volume `dbdata`, поднять контейнеры. Потерь нет, т.к. старый volume не изменялся.

## Вариант B: in-place upgrade 8.0 → 8.4

MySQL поддерживает прямой апгрейд 8.0 → 8.4, но откат сложнее (downgrade не поддерживается).

1. Сделать снапшот сервера/volume и дамп БД.
2. Остановить приложение и БД.
3. Заменить образ на `mysql:8.4` (тот же volume).
4. Запустить БД; MySQL обновит data dictionary автоматически.
5. Проверить лог на ошибки, выполнить `mysqlcheck --all-databases`, прогнать проверки из варианта A.

## Окно и риски

- Ожидаемый простой: 15–30 минут (вариант A), 10–20 минут (вариант B).
- Риски: несовместимые collation/кодировки, отключённый `mysql_native_password`, устаревшие SQL-хуки.
- Обязательно: дамп + проверка восстановления, снапшот сервера, план отката.

## После обновления

- Обновить `docker-compose.yml` (образ `mysql:8.4`).
- Зафиксировать версию в `changelog.md` и `architecture.md` (раздел стека).
- Настроить регулярный бэкап БД (cron + выгрузка вне сервера).
