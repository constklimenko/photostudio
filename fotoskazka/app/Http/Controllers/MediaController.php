<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function original(Media $media): StreamedResponse
    {
        $disk = Storage::disk($media->disk ?? 'public');

        abort_unless($media->file_path && $disk->exists($media->file_path), 404);

        return response()->stream(
            function () use ($disk, $media): void {
                fpassthru($disk->readStream($media->file_path));
            },
            200,
            [
                'Content-Type' => $media->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.basename($media->file_path).'"',
                'Cache-Control' => 'public, max-age=86400',
            ],
        );
    }
}
