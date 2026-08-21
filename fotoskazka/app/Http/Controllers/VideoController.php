<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\PageContentService;

class VideoController extends Controller
{
    public function index(PageContentService $pageContent)
    {
        $page = $pageContent->get('video');

        $videos = Video::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'url', 'file_path', 'type', 'sort_order']);

        $horizontalVideos = $videos->where('type', 'horizontal');
        $verticalVideos = $videos->where('type', 'vertical');

        return view('video.index', compact(
            'page',
            'videos',
            'horizontalVideos',
            'verticalVideos',
        ));
    }
}
