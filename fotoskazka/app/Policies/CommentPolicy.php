<?php

namespace App\Policies;

use App\Models\Photo;
use App\Models\Project;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user, Project|Photo $commentable): bool
    {
        return $user->can('view', $commentable);
    }
}
