<?php

namespace App\Policies;

use App\Models\ArTarget;
use App\Models\User;

class ArTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function view(User $user, ArTarget $target): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function update(User $user, ArTarget $target): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function delete(User $user, ArTarget $target): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }
}
