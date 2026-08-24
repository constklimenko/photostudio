<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteMedia
{
    public function __construct(protected ImageCacheService $imageCache) {}

    /**
     * Удаляет Media согласно политике хранения этапа B6.
     *
     * Локальный оригинал удаляется всегда; оригинал на удалённом диске
     * (Яндекс.Диск) — только по решению пользователя ($deleteRemoteOriginal).
     * Производные (thumbnail, display/lightbox-кэш) удаляются в любом случае.
     *
     * Ошибка удаления запрошенного к удалению оригинала прерывает операцию:
     * запись Media сохраняется, чтобы не потерять ссылку на файл.
     * Ошибка удаления производных не блокирует удаление записи —
     * потерянный файл кэша не стоит записи в БД.
     *
     * @return bool true — запись удалена; false — запись сохранена из-за ошибки
     */
    public function execute(Media $media, bool $deleteRemoteOriginal = false): bool
    {
        $isRemote = $media->isRemoteDisk((string) $media->disk);

        if ((! $isRemote || $deleteRemoteOriginal) && ! $this->deleteOriginal($media)) {
            return false;
        }

        $this->deleteDerivatives($media);

        return (bool) $media->delete();
    }

    protected function deleteOriginal(Media $media): bool
    {
        $path = (string) $media->file_path;

        if ($path === '') {
            return true;
        }

        try {
            $disk = $this->originalDisk($media);

            if (! $disk->exists($path)) {
                return true;
            }

            return (bool) $disk->delete($path);
        } catch (Throwable $exception) {
            Log::error('Unable to delete media original, keeping the record.', [
                'media_id' => $media->id,
                'disk' => $media->disk,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function deleteDerivatives(Media $media): void
    {
        $context = [
            'media_id' => $media->id,
            'disk' => $media->disk,
            'path' => (string) $media->file_path,
        ];

        if ($media->thumbnail_path) {
            try {
                Storage::disk('thumbnails')->delete($media->thumbnail_path);
            } catch (Throwable $exception) {
                Log::warning('Unable to delete media thumbnail.', $context + [
                    'thumbnail_path' => $media->thumbnail_path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        try {
            $this->imageCache->forget($media);
        } catch (Throwable $exception) {
            Log::warning('Unable to delete media image cache variants.', $context + [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function originalDisk(Media $media): Filesystem
    {
        return Storage::disk($media->disk ?? (string) config('filesystems.default_media_disk', 'public'));
    }
}
