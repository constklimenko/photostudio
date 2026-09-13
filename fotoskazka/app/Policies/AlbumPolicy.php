<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;

class AlbumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function update(User $user, Album $album): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function delete(User $user, Album $album): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function view(User $user, Album $album): bool
    {
        if ($user->hasAnyRole(['admin', 'photographer'])) {
            return true;
        }

        if ($user->hasRole('client') && $album->project?->client_id === $user->id) {
            return true;
        }

        if ($user->hasRole('class_manager') && $album->type === 'client'
            && $album->project?->manager_id === $user->id) {
            return true;
        }

        if ($user->hasRole('parent') && $album->type === 'client'
            && $album->users()->where('users.id', $user->id)->exists()) {
            return true;
        }

        return false;
    }
}
