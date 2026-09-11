<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\CabinetService;
use Illuminate\Support\Facades\Gate;

class CabinetController extends Controller
{
    public function __construct(
        private readonly CabinetService $cabinet,
    ) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('parent')) {
            $albums = $this->cabinet->getAlbumsForUser($user);

            return view('cabinet.index', compact('user', 'albums'));
        }

        $projects = $this->cabinet->getProjectsForUser($user);

        return view('cabinet.index', compact('user', 'projects'));
    }

    public function projects()
    {
        $user = auth()->user();

        $projects = $this->cabinet->getProjectsForUser($user)->filter(
            fn ($project) => $user->can('view', $project),
        );

        return view('cabinet.projects', compact('user', 'projects'));
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $user = auth()->user();

        $project = $this->cabinet->getProjectForUser($user, $project->id);

        if (! $project) {
            abort(404);
        }

        $albums = $user->hasRole('class_manager')
            ? $project->albums->where('type', 'client')
            : $project->albums;

        return view('cabinet.project', compact('user', 'project', 'albums'));
    }
}
