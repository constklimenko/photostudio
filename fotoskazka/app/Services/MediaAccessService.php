<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class MediaAccessService
{
    private const PRIVATE_ALBUM_TYPES = ['client', 'project'];

    public function isPublic(Media $media): bool
    {
        return $this->privateAlbums($media)->count() === 0;
    }

    public function canView(Media $media, ?User $user): bool
    {
        $privateAlbums = $this->privateAlbums($media)->get();

        if ($privateAlbums->isEmpty()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $privateAlbums->contains(
            fn (Album $album): bool => $user->can('view', $album),
        );
    }

    /**
     * Приватные альбомы, связанные с Media:
     * - через строки Photo (цепочка Media → Photo → Album);
     * - через обложку альбома (Media из albums.cover_media_id).
     */
    private function privateAlbums(Media $media): Builder
    {
        return Album::query()
            ->whereIn('albums.type', self::PRIVATE_ALBUM_TYPES)
            ->where(function (Builder $query) use ($media): void {
                $query
                    ->where('albums.cover_media_id', $media->getKey())
                    ->orWhereExists(function (QueryBuilder $photos) use ($media): void {
                        $photos
                            ->from('photos')
                            ->whereColumn('photos.album_id', 'albums.id')
                            ->where('photos.media_id', $media->getKey());
                    });
            })
            ->with('project');
    }
}
