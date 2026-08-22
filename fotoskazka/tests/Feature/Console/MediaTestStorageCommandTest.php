<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTestStorageCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.disks.yandex_disk.token' => 'test-token',
            'filesystems.disks.yandex_disk.root' => 'fotoskazka/originals',
        ]);
        Storage::fake('yandex_disk');
    }

    public function test_fails_for_unknown_disk(): void
    {
        $this->artisan('media:test-storage', ['--disk' => 'unknown-disk'])
            ->expectsOutputToContain('не найден')
            ->assertFailed();
    }

    public function test_fails_without_token(): void
    {
        config(['filesystems.disks.yandex_disk.token' => null]);

        $this->artisan('media:test-storage')
            ->expectsOutputToContain('OAuth-токен не настроен')
            ->assertFailed();
    }

    public function test_passes_full_cycle_on_fake_disk(): void
    {
        $this->artisan('media:test-storage')
            ->expectsOutputToContain('Файл записан')
            ->expectsOutputToContain('содержимое совпадает')
            ->expectsOutputToContain('работает корректно')
            ->assertSuccessful();

        Storage::disk('yandex_disk')->assertDirectoryEmpty('_storage-test');
    }

    public function test_reports_root_directory(): void
    {
        $this->artisan('media:test-storage')
            ->expectsOutputToContain('fotoskazka/originals')
            ->assertSuccessful();
    }
}
