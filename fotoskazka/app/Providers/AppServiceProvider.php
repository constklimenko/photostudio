<?php

namespace App\Providers;

use App\Filesystem\YandexDiskPaginatedAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use ImpressiveWeb\YandexDisk\Client as YandexDiskClient;
use League\Flysystem\Filesystem as Flysystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction() && filled(config('app.url'))) {
            URL::forceRootUrl(rtrim(config('app.url'), '/'));
            URL::forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https');
        }

        Storage::extend('yandex-disk', function (Application $app, array $config): FilesystemAdapter {
            $client = new YandexDiskClient($config['token'] ?? '');

            $root = trim((string) ($config['root'] ?? ''), '/');

            if ($root !== '') {
                $client->setPathPrefix(
                    rtrim((string) ($config['path_prefix'] ?? 'disk:/'), '/').'/'.$root
                );
            } else {
                $client->setPathPrefix((string) ($config['path_prefix'] ?? 'disk:/'));
            }

            $adapter = new YandexDiskPaginatedAdapter($client);

            return new FilesystemAdapter(
                new Flysystem($adapter, $config),
                $adapter,
                $config,
            );
        });
    }
}
