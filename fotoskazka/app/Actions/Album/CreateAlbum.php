<?php

namespace App\Actions\Album;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use Illuminate\Support\Facades\DB;

class CreateAlbum
{
    public function execute(array $data): Album
    {
        return DB::transaction(function () use ($data) {
            $album = Album::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'portfolio',
                'project_id' => $data['type'] === 'project' ? ($data['project_id'] ?? null) : null,
                'slug' => str($data['title'])->slug()->append('-'.now()->format('YmdHis')),
            ]);

            if (! empty($data['cover'])) {
                $coverMedia = Media::create([
                    'file_path' => $data['cover'],
                    'disk' => 'public',
                    'collection' => 'covers',
                    'title' => $album->title,
                ]);
                $album->update(['cover_media_id' => $coverMedia->id]);
            }

            foreach ($data['photos'] as $index => $photoPath) {
                $media = Media::create([
                    'file_path' => $photoPath,
                    'disk' => 'public',
                    'collection' => 'gallery',
                    'title' => $album->title.' — '.($index + 1),
                ]);

                Photo::create([
                    'album_id' => $album->id,
                    'media_id' => $media->id,
                    'sort_order' => $index,
                ]);
            }

            return $album->fresh();
        });
    }
}
