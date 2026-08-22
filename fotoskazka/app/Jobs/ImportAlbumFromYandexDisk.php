<?php

namespace App\Jobs;

use App\Actions\Album\ImportAlbumFromYandexDisk as AlbumImportAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportAlbumFromYandexDisk implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public array $data,
        public string $diskName = 'yandex_disk',
    ) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(AlbumImportAction $action): void
    {
        $album = $action->execute($this->data, $this->diskName);

        Log::info('Album import from Yandex Disk finished.', [
            'album_id' => $album->id,
            'imported' => $album->imported_files_count ?? null,
            'skipped' => $album->skipped_files_count ?? null,
        ]);
    }
}
