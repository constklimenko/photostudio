<?php

namespace App\Actions\Album;

use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAlbum
{
    public function execute(array $data): Album
    {
        $defaultDisk = Config::get('filesystems.default_media_disk', 'public');

        return DB::transaction(function () use ($data, $defaultDisk) {
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
                'project_id' => $data['type'] === 'project' ? ($data['project_id'] ?? null) : null,
                'slug' => $slug,
            ]);

            if (! empty($data['cover'])) {
                $coverMedia = Media::create([
                    'file_path' => $data['cover'],
                    'disk' => $defaultDisk,
                    'collection' => 'covers',
                    'title' => $album->title,
                ]);
                $album->update(['cover_media_id' => $coverMedia->id]);
            }

            foreach ($data['photos'] as $index => $photoPath) {
                $media = Media::create([
                    'file_path' => $photoPath,
                    'disk' => $defaultDisk,
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
