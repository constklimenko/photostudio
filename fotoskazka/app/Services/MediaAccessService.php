<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    private function privateAlbums(Media $media): BelongsToMany
    {
        return $media->albums()
            ->whereIn('albums.type', self::PRIVATE_ALBUM_TYPES)
            ->with('project');
    }
}
