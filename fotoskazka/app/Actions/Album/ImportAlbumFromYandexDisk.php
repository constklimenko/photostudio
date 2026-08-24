<?php

namespace App\Actions\Album;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImportAlbumFromYandexDisk
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function execute(array $data, string $diskName = 'yandex_disk'): Album
    {
        $disk = Storage::disk($diskName);
        $folder = trim($data['folder'], " \t\n\r\0\x0B/");

        if ($folder === '' || ! $disk->directoryExists($folder)) {
            throw new RuntimeException("Папка [{$folder}] не найдена на диске [{$diskName}].");
        }

        $files = $this->collectImages($disk, $folder);

        if ($files->isEmpty()) {
            throw new RuntimeException("В папке [{$folder}] нет изображений.");
        }

        $maxFiles = (int) Config::get('filesystems.yandex_import.max_files', 100);
        $skippedCount = max(0, $files->count() - $maxFiles);
        $files = $files->take($maxFiles)->values();

        $album = DB::transaction(function () use ($data, $files, $diskName) {
            $base = Str::slug($data['title']);
            $slug = $base;
            $counter = 1;

            while (Album::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$counter++;
            }

            $album = Album::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'portfolio',
                'project_id' => ($data['type'] ?? 'portfolio') === 'project'
                    ? ($data['project_id'] ?? null)
                    : null,
                'slug' => $slug,
            ]);

            foreach ($files as $index => $path) {
                $media = Media::create([
                    'file_path' => $path,
                    'disk' => $diskName,
                    'collection' => 'gallery',
                    'title' => $album->title.' — '.($index + 1),
                ]);

                Photo::create([
                    'album_id' => $album->id,
                    'media_id' => $media->id,
                    'sort_order' => $index,
                ]);

                if ($index === 0 && ! empty($data['use_first_as_cover'])) {
                    $album->update(['cover_media_id' => $media->id]);
                }
            }

            return $album;
        });

        $album->imported_files_count = $files->count();
        $album->skipped_files_count = $skippedCount;

        return $album;
    }

    protected function collectImages($disk, string $folder)
    {
        return collect($disk->files($folder))
            ->filter(fn (string $path): bool => in_array(
                strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                self::IMAGE_EXTENSIONS,
                true,
            ))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }
}
