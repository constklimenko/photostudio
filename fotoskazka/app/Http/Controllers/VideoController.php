<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\PageContentService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    public function index(PageContentService $pageContent)
    {
        $page = $pageContent->get('video');

        $videos = Video::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'url', 'file_path', 'type', 'rotate_90', 'sort_order']);

        $horizontalVideos = $videos->where('type', 'horizontal');
        $verticalVideos = $videos->where('type', 'vertical');

        return view('video.index', compact(
            'page',
            'videos',
            'horizontalVideos',
            'verticalVideos',
        ));
    }

    public function stream(Video $video): StreamedResponse
    {
        abort_unless($video->file_path, 404);

        $disk = Storage::disk(Config::get('filesystems.default_media_disk', 'public'));

        abort_unless($disk->exists($video->file_path), 404);

        return response()->stream(
            function () use ($disk, $video): void {
                fpassthru($disk->readStream($video->file_path));
            },
            200,
            [
                'Content-Type' => 'video/mp4',
                'Content-Disposition' => 'inline; filename="'.addslashes(basename($video->file_path)).'"',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
                'Accept-Ranges' => 'bytes',
            ],
        );
    }
}
