<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['admin', 'photographer']);
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->hasAnyRole(['admin', 'photographer'])) {
            return true;
        }

        if ($user->hasRole('client') && $project->client_id === $user->id) {
            return true;
        }

        if ($user->hasRole('class_manager') && $project->manager_id === $user->id) {
            return true;
        }

        return false;
    }
}
