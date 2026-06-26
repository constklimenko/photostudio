# Database Structure

## Общая информация

Проект: Фотосказка

Стек:

* Laravel 13
* MySQL 8
* Filament 4

Назначение системы:

* сайт фотографа;
* блог;
* портфолио;
* заявки клиентов;
* клиентские галереи;
* выпускные альбомы.

---

# Основные сущности

## Пользователи

Все пользователи системы хранятся в одной таблице.

Роли определяются через таблицы:

* roles
* role_user

Поддерживаемые роли:

* admin
* photographer
* client
* parent
* class_manager

---

# ER Diagram

```mermaid
erDiagram

    USERS ||--o{ ROLE_USER : has
    ROLES ||--o{ ROLE_USER : assigned

    USERS ||--o{ PROJECTS : owns

    CATEGORIES ||--o{ SERVICES : categorizes
    CATEGORIES ||--o{ POSTS : categorizes

    PROJECTS ||--o{ ALBUMS : contains
    ALBUMS ||--o{ PHOTOS : contains

    MEDIA ||--o{ PHOTOS : source

    MEDIA ||--o{ POSTS : cover
    MEDIA ||--o{ SERVICES : cover
    MEDIA ||--o{ ALBUMS : cover
    MEDIA ||--o{ PAGES : cover
    MEDIA ||--o{ TESTIMONIALS : photo

    SERVICES ||--o{ INQUIRIES : receives

```

---

# Tables

## users

```sql
id BIGINT PRIMARY KEY

name VARCHAR(255)

email VARCHAR(255) UNIQUE

phone VARCHAR(50) NULL

email_verified_at TIMESTAMP NULL

password VARCHAR(255)

status ENUM(
    'active',
    'inactive'
) DEFAULT 'active'

remember_token VARCHAR(100)

created_at TIMESTAMP
updated_at TIMESTAMP
```

Foreign keys: (none)

Indexes:

```sql
UNIQUE(email)
INDEX(phone)
INDEX(status)
```

---

## roles

```sql
id BIGINT PRIMARY KEY

name VARCHAR(255)

slug VARCHAR(100) UNIQUE

is_system BOOLEAN DEFAULT TRUE

created_at TIMESTAMP
updated_at TIMESTAMP
```

Indexes:

```sql
UNIQUE(slug)
INDEX(is_system)
```

Начальные данные:

| slug          | name                    |
| ------------- | ----------------------- |
| admin         | Администратор           |
| photographer  | Фотограф                |
| client        | Клиент                  |
| parent        | Родитель                |
| class_manager | Ответственный по классу |

---

## role_user

```sql
user_id BIGINT

role_id BIGINT

PRIMARY KEY(user_id, role_id)
```

Foreign keys:

```sql
user_id -> users.id ON DELETE CASCADE
role_id -> roles.id ON DELETE CASCADE
```

### Система ролей

Пользователи могут иметь неограниченное количество ролей (many-to-many через `role_user`).

Системные роли (`is_system = true`) защищены от удаления и изменения `slug`.
Пользовательские роли (`is_system = false`) доступны для полного редактирования.

Поле `is_system` добавлено миграцией `add_is_system_to_roles_table`.

### Доступ в административную панель

Доступ к `/admin` (Filament) разрешён только пользователям:
- со статусом `active`;
- имеющим роль `admin`.

Метод `User::canAccessPanel()` проверяет оба условия.
Остальные пользователи перенаправляются на страницу входа.

---

## categories

Универсальные категории.

```sql
id BIGINT PRIMARY KEY

name VARCHAR(255)

slug VARCHAR(255)

type ENUM(
    'service',
    'post'
)

sort_order INT DEFAULT 0

created_at TIMESTAMP
updated_at TIMESTAMP
```

Indexes:

```sql
UNIQUE(slug, type)
INDEX(type)
INDEX(sort_order)
```

---

## media

Централизованное файловое хранилище.

```sql
id BIGINT PRIMARY KEY

title VARCHAR(255) NULL

alt_text VARCHAR(255) NULL

disk VARCHAR(50)

file_path VARCHAR(1000)

thumbnail_path VARCHAR(1000) NULL

mime_type VARCHAR(255) NULL

width INT UNSIGNED NULL

height INT UNSIGNED NULL

file_size BIGINT UNSIGNED NULL

collection VARCHAR(100) NULL

created_at TIMESTAMP
updated_at TIMESTAMP
```

Indexes:

```sql
INDEX(disk)
INDEX(created_at)
```

---

## mediaables

Полиморфная pivot-таблица для привязки медиа к любым сущностям.
Зарезервирована для будущего использования (модель `Mediaable` пока не создана).

```sql
id BIGINT PRIMARY KEY
media_id BIGINT
mediaable_type VARCHAR(255)
mediaable_id BIGINT UNSIGNED
sort_order INT DEFAULT 0
created_at TIMESTAMP
updated_at TIMESTAMP
```

Foreign keys:

```sql
media_id -> media.id ON DELETE CASCADE
```

Indexes:

```sql
INDEX(media_id)
INDEX(mediaable_type, mediaable_id)
INDEX(sort_order)
```

---

## Pages

```sql
id BIGINT PRIMARY KEY
cover_media_id BIGINT NULL
title VARCHAR(255)
slug VARCHAR(255) UNIQUE
excerpt TEXT NULL
content LONGTEXT NULL
seo_title VARCHAR(255) NULL
seo_description TEXT NULL
is_published BOOLEAN DEFAULT TRUE
sort_order INT DEFAULT 0
created_at TIMESTAMP
updated_at TIMESTAMP
```

Foreign keys:

```sql
cover_media_id -> media.id ON DELETE SET NULL
```

Indexes:

```sql
UNIQUE(slug)
INDEX(is_published)
INDEX(sort_order)
```

---

## services

```sql
id BIGINT PRIMARY KEY

category_id BIGINT NULL

cover_media_id BIGINT NULL

title VARCHAR(255)

slug VARCHAR(255) UNIQUE

short_description TEXT NULL

description LONGTEXT NULL

price_from DECIMAL(10,2) NULL

is_published BOOLEAN DEFAULT TRUE

sort_order INT DEFAULT 0

created_at TIMESTAMP
updated_at TIMESTAMP

seo_title VARCHAR(255) NULL
seo_description TEXT NULL
```

Foreign keys:

```sql
category_id -> categories.id ON DELETE SET NULL
cover_media_id -> media.id ON DELETE SET NULL
```

Indexes:

```sql
UNIQUE(slug)
INDEX(category_id)
INDEX(is_published)
INDEX(sort_order)
```

---

## projects

Универсальная сущность съёмки.

Может представлять:

* свадьбу;
* выпускной класс;
* детский сад;
* индивидуальную фотосессию;
* семейную съёмку;
* мероприятие.

```sql
id BIGINT PRIMARY KEY

client_id BIGINT NULL

manager_id BIGINT NULL

title VARCHAR(255)

slug VARCHAR(255) NULL UNIQUE

type ENUM(
    'individual',
    'family',
    'event',
    'wedding',
    'school',
    'kindergarten'
)

description TEXT NULL

shooting_date DATE NULL

status ENUM(
    'draft',
    'active',
    'completed',
    'archived'
)

created_at TIMESTAMP
updated_at TIMESTAMP
```

Foreign keys:

```sql
client_id -> users.id ON DELETE SET NULL
manager_id -> users.id ON DELETE SET NULL
```

Indexes:

```sql
INDEX(client_id)
INDEX(manager_id)
INDEX(type)
INDEX(status)
INDEX(shooting_date)
```

---

## albums

Фотоальбом.

```sql
id BIGINT PRIMARY KEY

project_id BIGINT NULL

cover_media_id BIGINT NULL

title VARCHAR(255)

slug VARCHAR(255) UNIQUE

description TEXT NULL

type VARCHAR(20) DEFAULT 'portfolio'

is_featured BOOLEAN DEFAULT FALSE

is_published BOOLEAN DEFAULT TRUE

sort_order INT DEFAULT 0

created_at TIMESTAMP
updated_at TIMESTAMP

seo_title VARCHAR(255) NULL
seo_description TEXT NULL
```

Возможные значения type:

| type       | Назначение                 |
| ---------- | -------------------------- |
| portfolio  | Галереи портфолио          |
| project    | Альбомы съёмок             |
| homepage   | Слайдеры главной страницы  |
| service    | Галереи услуг              |
| client     | Клиентские галереи         |

Foreign keys:

```sql
project_id -> projects.id ON DELETE SET NULL
cover_media_id -> media.id ON DELETE SET NULL
```

Indexes:

```sql
UNIQUE(slug)
INDEX(project_id)
INDEX(is_featured)
INDEX(is_published)
INDEX(type)
```

---

## photos

Фотографии альбома.

```sql
id BIGINT PRIMARY KEY

album_id BIGINT

media_id BIGINT

caption VARCHAR(255) NULL

sort_order INT DEFAULT 0

created_at TIMESTAMP
updated_at TIMESTAMP
```

Foreign keys:

```sql
album_id -> albums.id ON DELETE CASCADE
media_id -> media.id ON DELETE CASCADE
```

Indexes:

```sql
INDEX(album_id)
INDEX(media_id)
INDEX(sort_order)
```

---

## posts

Блог.

```sql
id BIGINT PRIMARY KEY

category_id BIGINT NULL

cover_media_id BIGINT NULL

title VARCHAR(255)

slug VARCHAR(255) UNIQUE

excerpt TEXT NULL

content LONGTEXT

published_at DATETIME NULL

is_published BOOLEAN DEFAULT TRUE

created_at TIMESTAMP
updated_at TIMESTAMP

seo_title VARCHAR(255) NULL
seo_description TEXT NULL
```

Foreign keys:

```sql
category_id -> categories.id ON DELETE SET NULL
cover_media_id -> media.id ON DELETE SET NULL
```

Indexes:

```sql
UNIQUE(slug)
INDEX(category_id)
INDEX(is_published)
INDEX(published_at)
```

---

## testimonials

Отзывы клиентов.

```sql
id BIGINT PRIMARY KEY

media_id BIGINT NULL

client_name VARCHAR(255)

content TEXT

sort_order INT DEFAULT 0

is_published BOOLEAN DEFAULT TRUE

created_at TIMESTAMP
updated_at TIMESTAMP
```

Foreign keys:

```sql
media_id -> media.id ON DELETE SET NULL
```

Indexes:

```sql
INDEX(is_published)
INDEX(sort_order)
```

---

## inquiries

Заявки.

```sql
id BIGINT PRIMARY KEY

user_id BIGINT NULL

service_id BIGINT NULL

name VARCHAR(255)

phone VARCHAR(50)

email VARCHAR(255) NULL

message TEXT NULL

agreed_to_terms BOOLEAN DEFAULT FALSE

shooting_date DATE NULL

status ENUM(
    'new',
    'in_progress',
    'completed',
    'cancelled'
)

created_at TIMESTAMP
updated_at TIMESTAMP
```

Foreign keys:

```sql
user_id -> users.id ON DELETE SET NULL
service_id -> services.id ON DELETE SET NULL
```

Indexes:

```sql
INDEX(user_id)
INDEX(service_id)
INDEX(status)
INDEX(phone)
INDEX(created_at)
```

---

## page_album

Pivot-таблица для связи страниц с альбомами (many-to-many).

```sql
page_id BIGINT
album_id BIGINT

PRIMARY KEY(page_id, album_id)
```

Foreign keys:

```sql
page_id -> pages.id ON DELETE CASCADE
album_id -> albums.id ON DELETE CASCADE
```

---

## post_album

Pivot-таблица для связи статей с альбомами (many-to-many).

```sql
post_id BIGINT
album_id BIGINT

PRIMARY KEY(post_id, album_id)
```

Foreign keys:

```sql
post_id -> posts.id ON DELETE CASCADE
album_id -> albums.id ON DELETE CASCADE
```

---

# Storage Strategy

Текущий этап:

```text
Laravel Filesystem
↓
Local/Public Storage
```

MediaObserver генерирует WebP-превью (400px) в директории `thumbnails/`.

Будущий этап:

```text
Laravel Filesystem
↓
Yandex Disk API (оригиналы)
↓
Local Thumbnail Cache (превью)
```

Все обращения к файлам должны происходить через Laravel Storage API.

---

# Planned Future Extensions

Будущие сущности:

* project_users
* graduation_classes
* graduation_students
* orders
* payments
* chat_messages
* notifications
* yandex_disk_sync

Текущая архитектура должна позволять их добавление без изменения существующих таблиц.
