# Редизайн страницы /services

## 1. Анализ референса (Package List – Melissa)

Страница построена вокруг **пакетов услуг** (package = service). Каждый пакет — это законченное торговое предложение с фиксированной ценой, набором характеристик и CTA.

### Структура одного пакета (блока услуг):

```
┌──────────────────────────────────────────────────┐
│  [изображение]  │  Название пакета               │
│                 │  Описание (1–2 предложения)     │
│                 │                                │
│                 │  Список характеристик:          │
│                 │  ✓ 1 Hours on Location         │
│                 │  ✓ 2 Outfit Changes            │
│                 │  ✓ 90 Images                   │
│                 │  ✓ 30 Low Resolution Images    │
│                 │  ✓ 60 High Resolution Images   │
│                 │  ✓ Cinematography              │
│                 │                                │
│                 │  *Примечание по доп. услугам    │
│                 │  [Book Appointment]             │
└──────────────────────────────────────────────────┘
```

Характеристики разделены на две колонки. Каждая помечена иконкой ✓.

На странице — несколько пакетов, следующих друг за другом. У каждого свой заголовок, описание, цена, набор характеристик, CTA.

## 2. Текущее состояние /services

### Таблица `services` (существующая)
| Поле | Тип | Назначение |
|---|---|---|
| id | PK | |
| category_id | FK → categories | Категория (Выпускные альбомы, Свадьбы и т.д.) |
| cover_media_id | FK → media | Обложка |
| title | string | Название |
| slug | string unique | URL |
| short_description | text | Краткое описание |
| description | longtext | Полное описание (RichEditor) |
| price_from | decimal(10,2) | Цена от |
| is_published | boolean | Публикация |
| sort_order | integer | Порядок |
| seo_title / seo_description | string/text | SEO |

### Проблема
В `description` (longtext, RichEditor) сейчас хранится всё подряд: и "о чём услуга", и "что входит", и примечания. Нет структуры:
- Нельзя показать список характеристик в виде иконок-галочек
- Нельзя показать цену как число, а не в тексте
- Нельзя сгруппировать характеристики колонками
- Нельзя сортировать или скрывать отдельные пункты

## 3. Предложение по изменению схемы БД

### 3.1. Новая таблица: `service_items`

Характеристики (ретушь, печать, групповые фото, видеосъёмка и т.п.) выносятся в отдельную таблицу.

```sql
CREATE TABLE service_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id      BIGINT UNSIGNED NOT NULL,
    label           VARCHAR(255) NOT NULL,            -- "Ретушь", "Печать фото"
    is_included     BOOLEAN DEFAULT TRUE,              -- true = галочка, false = минус/нет
    sort_order      INTEGER DEFAULT 0,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX (service_id),
    INDEX (sort_order)
);
```

**Почему внешний ключ, а не JSON:**
- Можно управлять через Filament `Repeater` на форме услуги
- Можно сортировать drag-n-drop
- Можно делать запросы (например, "все услуги с видеосъёмкой")
- Нормализованная структура (правило проекта — избегать JSON)

### 3.2. Новая таблица: `service_prices`

Если цены у услуги сложные (несколько тарифов/вариантов), завести отдельную таблицу. **MVP-решение**: оставить `price_from` в `services` и добавить опциональное поле `price_note` для примечаний.

**Итоговые изменения в `services`:**
- Добавить `price_note TEXT NULL` — примечание к цене (например, «*За дополнительный час +2000₽»)

### 3.3. Модели

#### ServiceItem (новая)
```php
class ServiceItem extends Model
{
    protected $fillable = ['service_id', 'label', 'is_included', 'sort_order'];
    protected $casts = ['is_included' => 'boolean'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
```

#### Service (дополнить)
```php
class Service extends Model
{
    // ... существующие поля и связи

    public function items(): HasMany
    {
        return $this->hasMany(ServiceItem::class)->orderBy('sort_order');
    }
}
```

## 4. Форма Filament (ServiceForm / ServiceResource)

Добавить в форму услуги `Repeater` для `items`:

```php
Repeater::make('items')
    ->relationship()
    ->schema([
        TextInput::make('label')->required()->maxLength(255)->label('Пункт'),
        Toggle::make('is_included')->default(true)->label('Включено'),
    ])
    ->orderColumn('sort_order')
    ->reorderable()
    ->addActionLabel('Добавить пункт')
    ->columns(2)
    ->columnSpanFull(),
```

В таблицу `ServicesTable` добавить колонку с количеством пунктов (count items).

## 5. Публичная страница /services (Blade)

### Страница списка (`services/index.blade.php`)

Каждая услуга отображается как полноценный блок с:

```
┌───────────────────────────────────────────────┐
│  [cover]                  │  [текстовый блок]  │
│  (слева/справа)           │  Заголовок           │
│                           │  short_description   │
│                           │                      │
│                           │  Список items:       │
│                           │  ✓ Пункт 1   ✓ Пункт 2
│                           │  ✓ Пункт 3   ✓ Пункт 4
│                           │                      │
│                           │  price_from ₽         │
│                           │  price_note           │
│                           │  [Записаться]         │
└───────────────────────────────────────────────┘
```

**Layout:**
- Если у услуги есть `cover` — показать слева или справа (чередование, как в референсе)
- Если нет — только текст на всю ширину
- Items разделены на две колонки (Tailwind `columns-2`)
- Перед каждым item — иконка ✓ (зелёная) или — (серая, если `is_included = false`)

**Группировка по категориям** сохраняется (как сейчас):
- Категории с их заголовками
- Услуги без категории — внизу

### Страница детально (`services/show.blade.php`)

Оставить как есть, но добавить вывод `items` тем же способом.

## 6. Миграция данных

1. Создать миграцию `create_service_items_table`
2. Создать миграцию `add_price_note_to_services` (если нужно)
3. Перенести существующие данные:
   - В `service_items` **ничего** не переносится, потому что в `description` данные неструктурированные — пользователь наполнит items через админку
   - Можно в будущем сделать Artisan-команду для парсинга HTML-списков из `description`

## 7. План реализации

| Шаг | Действие | Файлы |
|---|---|---|
| 1 | Миграция `create_service_items_table` | `database/migrations/` |
| 2 | Миграция `add_price_note_to_services` (опционально) | `database/migrations/` |
| 3 | Модель `ServiceItem` | `app/Models/ServiceItem.php` |
| 4 | Дополнить `Service` связью `items()` | `app/Models/Service.php` |
| 5 | Тест связей модели ServiceItem | `tests/Unit/Models/` |
| 6 | Обновить `ServiceForm` (Repeater) + `ServiceTable` (count) | `app/Filament/Resources/Services/` |
| 7 | Обновить `ServiceController` — eager load items | `app/Http/Controllers/ServiceController.php` |
| 8 | Обновить `services/index.blade.php` | `resources/views/services/index.blade.php` |
| 9 | Обновить `services/show.blade.php` | `resources/views/services/show.blade.php` |
| 10 | Собрать Vite, проверить тесты | — |
| 11 | Обновить `changelog.md`, `database.md` | — |

## 8. Риски и решения

| Риск | Решение |
|---|---|
| `description` (RichEditor) сейчас содержит списки — после ввода items будет дублирование контента | Оставить `description` для "рассказа об услуге", items — для структурированного списка "что входит". Это разные сущности |
| Пользователь не захочет заполнять items | Не делать items обязательными. Если пусто — не показывать блок |
| Сложная цена (несколько тарифов) | Отложить до следующего этапа. В MVP — `price_from` + `price_note` |
| Items на главной странице | Сейчас на главной услуги показываются кратко (карточка). Если нужно — items можно показать при клике на "Подробнее" |

## 9. Пример контента

| Услуга | Items |
|---|---|
| Свадебная фотосъёмка (Silver) | 1 час съёмки, 2 образа, 90 фото, 30 в низком разрешении, 60 RAW |
| Свадебная фотосъёмка (Gold) | 4 часа съёмки, 3 образа, 300+ фото, вся ретушь, видеосъёмка, печать |
| Индивидуальная съёмка | 1 час, 2 образа, 50 фото, ретушь 10 фото, локация в студии |
| Выпускной альбом | 30 разворотов, дизайн-макет, печать, доставка |
