<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\PageContentService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    private const MAX_RANGES = 5;

    public function index(PageContentService $pageContent)
    {
        $page = $pageContent->get('video');

        $videos = Video::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'url', 'file_path', 'type', 'rotation', 'has_sound', 'sort_order']);

        $horizontalVideos = $videos->where('type', 'horizontal');
        $verticalVideos = $videos->where('type', 'vertical');

        return view('video.index', compact(
            'page',
            'videos',
            'horizontalVideos',
            'verticalVideos',
        ));
    }

    public function stream(Request $request, Video $video): Response
    {
        abort_unless($video->file_path, 404);

        $file = $video->file_path;
        $disk = Storage::disk(Config::get('filesystems.default_media_disk', 'public'));

        if (! $disk->exists($file)) {
            abort(404);
        }

        $filename = basename($file);

        if (Config::get('filesystems.video_x_accel') && $disk->getAdapter() instanceof LocalFilesystemAdapter) {
            return $this->accelRedirect($file, $filename);
        }

        $size = $disk->size($file);
        $etag = '"'.md5($file.':'.$size).'"';

        $baseHeaders = [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'private, max-age=86400, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Accept-Ranges' => 'bytes',
            'ETag' => $etag,
        ];

        if ($request->isMethod('GET')) {
            $ifNoneMatch = $request->header('If-None-Match');

            if ($ifNoneMatch && $this->matchesEtag($ifNoneMatch, $etag)) {
                return new StreamedResponse(fn () => null, 304, $baseHeaders);
            }
        }

        $since = null;
        if ($request->hasHeader('If-Range')) {
            $since = $request->header('If-Range');
        }

        $range = $request->header('Range');

        if (! $this->rangeStillValid($since, $etag)) {
            $range = null;
        }

        if ($range && substr_count($range, ',') >= self::MAX_RANGES) {
            $range = null;
        }

        $ranges = $range ? $this->parseRanges($range, $size) : [];

        if ($range && $ranges === []) {
            return new StreamedResponse(
                fn () => null,
                416,
                array_merge($baseHeaders, ['Content-Range' => 'bytes */'.$size]),
            );
        }

        if ($ranges === []) {
            return $this->fullStream($disk, $file, $size, $baseHeaders);
        }

        if (count($ranges) === 1) {
            return $this->singleRangeStream($disk, $file, $size, $ranges[0], $baseHeaders);
        }

        return $this->multiRangeStream($disk, $file, $size, $ranges, $baseHeaders);
    }

    protected function fullStream(Filesystem $disk, string $file, int $size, array $headers): StreamedResponse
    {
        return new StreamedResponse(
            fn () => $this->streamBytes($disk, $file, 0, $size - 1),
            200,
            array_merge($headers, ['Content-Length' => (string) $size]),
        );
    }

    protected function singleRangeStream(Filesystem $disk, string $file, int $size, array $range, array $headers): StreamedResponse
    {
        [$start, $end] = $range;

        return new StreamedResponse(
            fn () => $this->streamBytes($disk, $file, $start, $end),
            206,
            array_merge($headers, [
                'Content-Range' => 'bytes '.$start.'-'.$end.'/'.$size,
                'Content-Length' => (string) ($end - $start + 1),
            ]),
        );
    }

    protected function multiRangeStream(Filesystem $disk, string $file, int $size, array $ranges, array $headers): StreamedResponse
    {
        $boundary = 'fotoskazka-'.bin2hex(random_bytes(8));

        $response = new StreamedResponse(function () use ($disk, $file, $size, $ranges, $boundary): void {
            foreach ($ranges as [$start, $end]) {
                echo "--{$boundary}\r\n"
                    ."Content-Type: video/mp4\r\n"
                    ."Content-Range: bytes {$start}-{$end}/{$size}\r\n\r\n";

                $this->streamBytes($disk, $file, $start, $end);
                echo "\r\n";
            }

            echo "--{$boundary}--\r\n";
        }, 206, array_merge($headers, [
            'Content-Type' => 'multipart/byteranges; boundary='.$boundary,
        ]));

        return $response;
    }

    /**
     * Отдача через nginx: PHP файл не читает, Range/ETag/If-Range/304
     * обрабатывает internal location (см. docker/nginx/generate-config.sh).
     */
    protected function accelRedirect(string $file, string $filename): Response
    {
        $location = rtrim((string) Config::get('filesystems.video_x_accel_location', '/protected-videos/'), '/');
        $uri = $location.'/'.implode('/', array_map('rawurlencode', explode('/', ltrim($file, '/'))));

        return new Response('', 200, [
            'X-Accel-Redirect' => $uri,
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'private, max-age=86400, immutable',
        ]);
    }

    protected function streamBytes(Filesystem $disk, string $file, int $start, int $end): void
    {
        $handle = $disk->readStream($file);

        if (! is_resource($handle)) {
            return;
        }

        if (stream_get_meta_data($handle)['seekable']) {
            fseek($handle, $start);
        } else {
            $skip = $start;

            while ($skip > 0 && ! feof($handle)) {
                $buffer = fread($handle, (int) min(8192, $skip));

                if ($buffer === false || $buffer === '') {
                    break;
                }

                $skip -= strlen($buffer);
            }
        }

        $chunk = 8192;
        $remaining = $end - $start + 1;

        while ($remaining > 0 && ! feof($handle)) {
            $read = (int) min($chunk, $remaining);
            $buffer = fread($handle, $read);

            if ($buffer === false || strlen($buffer) === 0) {
                break;
            }

            echo $buffer;
            $remaining -= strlen($buffer);
        }

        fclose($handle);
    }

    protected function parseRanges(string $header, int $size): array
    {
        if (! preg_match('/^bytes=/i', $header)) {
            return [];
        }

        $specs = preg_split('/\s*,\s*/', (string) preg_replace('/^bytes=/i', '', trim($header)));

        $ranges = [];

        foreach ($specs as $spec) {
            if (! preg_match('/^(\d*)-(\d*)$/', $spec, $match)) {
                continue;
            }

            $startRaw = $match[1];
            $endRaw = $match[2];

            if ($startRaw === '' && $endRaw === '') {
                continue;
            }

            if ($startRaw === '') {
                $suffix = (int) $endRaw;

                if ($suffix <= 0) {
                    continue;
                }

                $start = max(0, $size - $suffix);
                $end = max(0, $size - 1);

                if ($start > $end) {
                    continue;
                }

                $ranges[] = [$start, $end];

                continue;
            }

            $start = (int) $startRaw;

            if ($start >= $size) {
                continue;
            }

            $end = $endRaw === '' ? $size - 1 : (int) $endRaw;
            $end = min($end, $size - 1);

            if ($start > $end) {
                continue;
            }

            $ranges[] = [$start, $end];
        }

        return $ranges;
    }

    protected function rangeStillValid(?string $since, string $etag): bool
    {
        if ($since === null) {
            return true;
        }

        $valid = preg_replace('/^"|"$/u', '', $since) === preg_replace('/^"|"$/u', '', $etag);

        return $valid;
    }

    protected function matchesEtag(string $ifNoneMatch, string $etag): bool
    {
        foreach (explode(',', $ifNoneMatch) as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '*') {
                return true;
            }

            if (ltrim($candidate, 'W/') === $etag) {
                return true;
            }
        }

        return false;
    }
}
