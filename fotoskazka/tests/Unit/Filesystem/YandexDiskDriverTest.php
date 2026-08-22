<?php

namespace Tests\Unit\Filesystem;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use ImpressiveWeb\Flysystem\YandexDiskAdapter;
use ImpressiveWeb\YandexDisk\Client as YandexDiskClient;
use ReflectionProperty;
use Tests\TestCase;

class YandexDiskDriverTest extends TestCase
{
    public function test_disk_config_is_registered(): void
    {
        $disk = config('filesystems.disks.yandex_disk');

        $this->assertIsArray($disk);
        $this->assertSame('yandex-disk', $disk['driver']);
        $this->assertTrue($disk['remote']);
        $this->assertTrue($disk['throw']);
        $this->assertSame(env('YANDEX_DISK_PATH_PREFIX', 'disk:/'), $disk['path_prefix']);
        $this->assertSame(env('YANDEX_DISK_ROOT', 'fotoskazka/originals'), $disk['root']);
    }

    public function test_token_comes_from_environment_only(): void
    {
        config(['filesystems.disks.yandex_disk.token' => env('YANDEX_DISK_TOKEN')]);

        $this->assertSame(env('YANDEX_DISK_TOKEN'), config('filesystems.disks.yandex_disk.token'));
    }

    public function test_disk_resolves_to_filesystem_adapter(): void
    {
        config(['filesystems.disks.yandex_disk.token' => 'test-token']);

        $disk = Storage::disk('yandex_disk');

        $this->assertInstanceOf(FilesystemAdapter::class, $disk);
        $this->assertInstanceOf(YandexDiskAdapter::class, $disk->getAdapter());
    }

    public function test_root_directory_is_applied_as_client_prefix(): void
    {
        config([
            'filesystems.disks.yandex_disk' => [
                'driver' => 'yandex-disk',
                'token' => 'test-token',
                'path_prefix' => 'disk:/',
                'root' => 'fotoskazka/originals',
                'remote' => true,
                'throw' => true,
            ],
        ]);

        $client = $this->resolveClient();

        $prefixProperty = new ReflectionProperty(YandexDiskClient::class, 'pathPrefix');

        $this->assertSame(
            'disk:/fotoskazka/originals',
            $prefixProperty->getValue($client),
        );
    }

    public function test_empty_root_keeps_bare_prefix(): void
    {
        config([
            'filesystems.disks.yandex_disk' => [
                'driver' => 'yandex-disk',
                'token' => 'test-token',
                'path_prefix' => 'app:/',
                'root' => '',
                'remote' => true,
                'throw' => true,
            ],
        ]);

        $client = $this->resolveClient();

        $prefixProperty = new ReflectionProperty(YandexDiskClient::class, 'pathPrefix');

        $this->assertSame('app:/', $prefixProperty->getValue($client));
    }

    protected function resolveClient(): YandexDiskClient
    {
        /** @var YandexDiskAdapter $adapter */
        $adapter = Storage::disk('yandex_disk')->getAdapter();

        $clientProperty = new ReflectionProperty(YandexDiskAdapter::class, 'client');

        return $clientProperty->getValue($adapter);
    }
}
