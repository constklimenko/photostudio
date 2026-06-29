<?php

namespace App\Http\Controllers;

use App\Models\Album;

class PortfolioController extends Controller
{
    public function index()
    {
        $albums = Album::query()
            ->where('type', 'portfolio')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug', 'description']);

        return view('portfolio.index', compact('albums'));
    }

    public function show(string $slug)
    {
        $album = Album::query()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->with(['cover', 'photos' => fn ($q) => $q->orderBy('sort_order')->with('media')])
            ->firstOrFail();

        return view('portfolio.show', compact('album'));
    }
}
