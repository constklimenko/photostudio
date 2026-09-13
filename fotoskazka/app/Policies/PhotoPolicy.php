<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\Photo;
use App\Models\User;

class PhotoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function create(User $user, ?Album $album = null): bool
    {
        if ($album) {
            return $user->can('update', $album);
        }

        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function update(User $user, Photo $photo): bool
    {
        return $user->can('update', $photo->album);
    }

    public function delete(User $user, Photo $photo): bool
    {
        return $user->can('delete', $photo->album);
    }

    public function view(User $user, Photo $photo): bool
    {
        return $user->can('view', $photo->album);
    }
}
