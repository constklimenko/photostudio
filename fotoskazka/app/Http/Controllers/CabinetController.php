<?php

namespace App\Http\Controllers;

use App\Services\CabinetService;

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
}
