<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    'default_media_disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'thumbnails' => [
            'driver' => 'local',
            'root' => storage_path('app/public/thumbnails'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage/thumbnails',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Кэш производных изображений (display / lightbox)
        |----------------------------------------------------------------------
        |
        | Локальный кэш PNG-версий для страниц портфолио:
        |   - "display"  (800px)  — сетка на странице альбома;
        |   - "lightbox" (1600px) — полноэкранный просмотр.
        |
        | Генерируются лениво при первом запросе и отдаются через прокси-роуты,
        | поэтому диск не обязан быть публичным. При превышении размера
        | (filesystems.image_cache.max_size_mb) самые старые файлы удаляются.
        |
        */

        'image_cache' => [
            'driver' => 'local',
            'root' => storage_path('app/image-cache'),
            'throw' => false,
            'report' => false,
        ],

        /*
    |----------------------------------------------------------------------
    | Yandex Disk (оригиналы изображений)
    |----------------------------------------------------------------------
    |
    | Удалённый диск для хранения оригиналов. OAuth-токен и параметры
    | задаются только через environment variables.
    |
    | "path_prefix" — схема доступа: "disk:/" (весь диск) или "app:/"
    | (папка приложения на Диске).
    |
    | "root" — корневая директория внутри префикса, относительно которой
    | работают все пути этого диска. Бизнес-логика не должна использовать
    | абсолютные пути Диска напрямую.
    |
    | "remote" => true — маркер того, что диск не отдаёт публичные URL:
    | файлы отдаются через прокси-роут media.original.
    |
    */

        'yandex_disk' => [
            'driver' => 'yandex-disk',
            'token' => env('YANDEX_DISK_TOKEN'),
            'path_prefix' => env('YANDEX_DISK_PATH_PREFIX', 'disk:/'),
            'root' => env('YANDEX_DISK_ROOT', 'fotoskazka/originals'),
            'remote' => true,
            'throw' => true,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Параметры кэша изображений
    |--------------------------------------------------------------------------
    |
    | tiers      — максимальная сторона (px) для каждого уровня кэша;
    | disk       — диск хранения кэша;
    | max_size_mb— лимит суммарного размера кэша; при превышении
    |              вытесняются самые старые файлы;
    | png_level  — уровень сжатия PNG (0-9).
    |
    */

    'image_cache' => [
        'disk' => env('IMAGE_CACHE_DISK', 'image_cache'),
        'tiers' => [
            'display' => 800,
            'lightbox' => 1600,
        ],
        'max_size_mb' => (int) env('IMAGE_CACHE_MAX_MB', 2048),
        'png_level' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
