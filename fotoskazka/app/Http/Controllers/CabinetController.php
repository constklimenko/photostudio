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
}
