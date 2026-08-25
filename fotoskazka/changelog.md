# Changelog

## 2026-08-26 — Иконки пунктов услуг и подзаголовки

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
- Тесты: 383 проходят (+2)
- Assertions: 754
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
