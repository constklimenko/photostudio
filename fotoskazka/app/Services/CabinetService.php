<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Photo;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CabinetService
{
    public function getProjectsForUser(User $user): Collection
    {
        $query = $this->baseProjectQuery();

        $this->applyProjectRoleFilter($query, $user);

        return $query->get();
    }

    public function getProjectForUser(User $user, int $projectId): ?Model
    {
        $query = $this->baseProjectQuery()
            ->where('projects.id', $projectId);

        $this->applyProjectRoleFilter($query, $user);

        return $query->first();
    }

    public function getAlbumsForUser(User $user): Collection
    {
        $query = $this->baseAlbumQuery()
            ->where('albums.type', 'client');

        $this->applyAlbumAccessFilter($query, $user);

        return $query->get();
    }

    public function getAlbumForUser(User $user, int $albumId): ?Model
    {
        $query = $this->baseAlbumQuery()
            ->where('albums.id', $albumId);

        $this->applyAlbumAccessFilter($query, $user);

        return $query->first();
    }

    public function getPhotosForAlbum(User $user, int $albumId): Collection
    {
        $album = $this->getAlbumForUser($user, $albumId);

        if (! $album) {
            return new Collection;
        }

        return Photo::query()
            ->where('album_id', $album->id)
            ->with('media')
            ->orderBy('sort_order')
            ->get();
    }

    private function baseProjectQuery(): Builder
    {
        return Project::query()
            ->with([
                'albums' => function ($q) {
                    $q->orderBy('sort_order');
                },
            ])
            ->withCount('albums as albums_count')
            ->withCount([
                'albums as client_albums_count' => fn ($q) => $q->where('type', 'client'),
            ])
            ->selectRaw('(select count(*) from photos inner join albums a on a.id = photos.album_id where a.project_id = projects.id) as photos_count')
            ->orderBy('created_at', 'desc');
    }

    private function applyProjectRoleFilter(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['admin', 'photographer'])) {
            return;
        }

        $query->where(function (Builder $q) use ($user) {
            $isClient = $user->hasRole('client');
            $isManager = $user->hasRole('class_manager');

            if (! $isClient && ! $isManager) {
                $q->whereRaw('1 = 0');

                return;
            }

            if ($isClient) {
                $q->where('client_id', $user->id);
            }

            if ($isManager) {
                $q->orWhere('manager_id', $user->id);
            }
        });
    }

    private function baseAlbumQuery(): Builder
    {
        return Album::query()
            ->with([
                'project',
                'cover',
                'users',
            ])
            ->orderBy('sort_order');
    }

    private function applyAlbumAccessFilter(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['admin', 'photographer'])) {
            return;
        }

        $query->where(function (Builder $q) use ($user) {
            $isClient = $user->hasRole('client');
            $isManager = $user->hasRole('class_manager');
            $isParent = $user->hasRole('parent');

            if (! $isClient && ! $isManager && ! $isParent) {
                $q->whereRaw('1 = 0');

                return;
            }

            if ($isClient) {
                $q->whereHas('project', fn (Builder $pq) => $pq->where('client_id', $user->id));
            }

            if ($isManager) {
                $q->orWhere(function (Builder $cq) use ($user) {
                    $cq->where('type', 'client')
                        ->whereHas('project', fn (Builder $pq) => $pq->where('manager_id', $user->id));
                });
            }

            if ($isParent) {
                $q->orWhere(function (Builder $pq) use ($user) {
                    $pq->where('type', 'client')
                        ->whereHas('users', fn (Builder $uu) => $uu->where('users.id', $user->id));
                });
            }
        });
    }
}
