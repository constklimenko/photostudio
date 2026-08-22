<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Albums\Pages\ImportFromYandexDisk;
use App\Jobs\ImportAlbumFromYandexDisk;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImportFromYandexDiskPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['status' => 'active']);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());
        $this->actingAs($admin);

        config(['filesystems.disks.yandex_disk.token' => 'test-token']);
        Storage::fake('yandex_disk');
    }

    public function test_import_page_renders_with_folder_select(): void
    {
        Storage::disk('yandex_disk')->makeDirectory('2025/vypusknoy-11a');
        Storage::disk('yandex_disk')->makeDirectory('2024/vypusknoy-9b');

        $response = $this->get('/admin/albums/import-yandex');

        $response->assertSuccessful();
    }

    public function test_import_page_renders_without_folders(): void
    {
        $response = $this->get('/admin/albums/import-yandex');

        $response->assertSuccessful();
    }

    public function test_create_dispatches_import_job_instead_of_sync_import(): void
    {
        Storage::disk('yandex_disk')->makeDirectory('японки');
        Queue::fake();

        Livewire::test(ImportFromYandexDisk::class)
            ->set('data.title', 'Японки')
            ->set('data.type', 'portfolio')
            ->set('data.folder_top', 'японки')
            ->call('create')
            ->assertHasNoErrors();

        Queue::assertPushed(ImportAlbumFromYandexDisk::class, function (ImportAlbumFromYandexDisk $job): bool {
            return $job->data['folder'] === 'японки'
                && $job->data['title'] === 'Японки';
        });
        $this->assertDatabaseCount('albums', 0);
    }
}
