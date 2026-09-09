<?php

namespace Tests\Unit\Services;

use App\Models\Media;
use App\Services\ImageCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ImageCacheService $service;

    protected string $tempCacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempCacheDir = sys_get_temp_dir().'/imgcache-test-'.uniqid('', true);
        mkdir($this->tempCacheDir, 0755, true);

        config(['filesystems.disks.image_cache.root' => $this->tempCacheDir]);
        app('filesystem')->forgetDisk('image_cache');
        mkdir($this->tempCacheDir.'/display', 0755, true);
        mkdir($this->tempCacheDir.'/lightbox', 0755, true);

        $this->service = new ImageCacheService;
        Storage::fake('public');
        Storage::fake('thumbnails');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempCacheDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempCacheDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->tempCacheDir);
        }

        parent::tearDown();
    }

    public function test_relative_path_is_deterministic(): void
    {
        $media = $this->createImageMedia('images/photo.jpg');

        $path1 = $this->service->relativePath($media, ImageCacheService::TIER_DISPLAY);
        $path2 = $this->service->relativePath($media, ImageCacheService::TIER_DISPLAY);

        $this->assertSame($path1, $path2);
    }

    public function test_relative_path_contains_tier_and_media_id(): void
    {
        $media = $this->createImageMedia('images/photo.jpg');

        $path = $this->service->relativePath($media, ImageCacheService::TIER_LIGHTBOX);

        $this->assertStringStartsWith('lightbox/', $path);
        $this->assertStringContainsString((string) $media->getKey(), $path);
        $this->assertStringEndsWith('.png', $path);
    }

    public function test_relative_path_changes_with_tier(): void
    {
        $media = $this->createImageMedia('images/photo.jpg');

        $displayPath = $this->service->relativePath($media, ImageCacheService::TIER_DISPLAY);
        $lightboxPath = $this->service->relativePath($media, ImageCacheService::TIER_LIGHTBOX);

        $this->assertNotSame($displayPath, $lightboxPath);
        $this->assertStringStartsWith('display/', $displayPath);
        $this->assertStringStartsWith('lightbox/', $lightboxPath);
    }

    public function test_relative_path_depends_on_media_identity(): void
    {
        $media1 = $this->createImageMedia('images/a.jpg');
        $media2 = $this->createImageMedia('images/b.jpg');

        $path1 = $this->service->relativePath($media1, ImageCacheService::TIER_DISPLAY);
        $path2 = $this->service->relativePath($media2, ImageCacheService::TIER_DISPLAY);

        $this->assertNotSame($path1, $path2);
    }

    public function test_url_returns_route_for_image_media(): void
    {
        $media = $this->createImageMedia('images/photo.jpg');

        $url = $this->service->url($media, ImageCacheService::TIER_DISPLAY);

        $this->assertSame(route('media.display', ['media' => $media->getKey()]), $url);
    }

    public function test_url_returns_null_for_non_image(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'docs/file.txt',
            'mime_type' => 'text/plain',
        ]);

        $this->assertNull($this->service->url($media, ImageCacheService::TIER_DISPLAY));
    }

    public function test_url_returns_null_for_null_mime_type(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'docs/file.txt',
            'mime_type' => null,
        ]);

        $this->assertNull($this->service->url($media, ImageCacheService::TIER_LIGHTBOX));
    }

    public function test_is_cached_returns_false_for_unknown_tier(): void
    {
        $media = $this->createImageMedia('images/photo.jpg');

        $this->assertFalse($this->service->isCached($media, 'nonexistent'));
    }

    public function test_is_cached_returns_false_for_non_image(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'docs/file.txt',
            'mime_type' => 'text/plain',
        ]);

        $this->assertFalse($this->service->isCached($media, ImageCacheService::TIER_DISPLAY));
    }

    public function test_is_cached_returns_true_when_file_exists(): void
    {
        $media = $this->createImageMedia('images/photo.jpg');
        $path = $this->service->relativePath($media, ImageCacheService::TIER_DISPLAY);

        file_put_contents($this->tempCacheDir.'/'.$path, 'cached-data');

        $this->assertTrue($this->service->isCached($media, ImageCacheService::TIER_DISPLAY));
    }

    public function test_ensure_cached_returns_path_when_file_exists(): void
    {
        $media = $this->createImageMedia('images/photo.jpg');
        $path = $this->service->relativePath($media, ImageCacheService::TIER_DISPLAY);

        file_put_contents($this->tempCacheDir.'/'.$path, 'cached-data');

        $result = $this->service->ensureCached($media, ImageCacheService::TIER_DISPLAY);

        $this->assertSame($path, $result);
    }

    public function test_ensure_cached_generates_when_missing(): void
    {
        $media = $this->createImageMedia('images/photo.jpg', 1200, 800);

        $result = $this->service->ensureCached($media, ImageCacheService::TIER_DISPLAY);

        $this->assertNotNull($result);
        $this->assertFileExists($this->tempCacheDir.'/'.$result);
    }

    public function test_ensure_cached_returns_null_for_non_image(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'docs/file.txt',
            'mime_type' => 'text/plain',
        ]);

        $this->assertNull($this->service->ensureCached($media, ImageCacheService::TIER_DISPLAY));
    }

    public function test_ensure_cached_returns_null_for_unknown_tier(): void
    {
        $media = $this->createImageMedia('images/photo.jpg');

        $this->assertNull($this->service->ensureCached($media, 'nonexistent'));
    }

    public function test_purge_returns_zero_when_under_limit(): void
    {
        config(['filesystems.image_cache.max_size_mb' => 100]);

        $path = 'display/small.png';
        file_put_contents($this->tempCacheDir.'/'.$path, str_repeat('a', 1024));

        $freed = $this->service->purgeToLimit();

        $this->assertSame(0, $freed);
        $this->assertFileExists($this->tempCacheDir.'/'.$path);
    }

    public function test_purge_returns_zero_when_cache_empty(): void
    {
        config(['filesystems.image_cache.max_size_mb' => 100]);

        $freed = $this->service->purgeToLimit();

        $this->assertSame(0, $freed);
    }

    public function test_purge_removes_oldest_when_over_limit(): void
    {
        config(['filesystems.image_cache.max_size_mb' => 0.7]);

        file_put_contents($this->tempCacheDir.'/display/old1.png', str_repeat('a', 600 * 1024));
        touch($this->tempCacheDir.'/display/old1.png', time() - 300);

        file_put_contents($this->tempCacheDir.'/display/old2.png', str_repeat('a', 600 * 1024));
        touch($this->tempCacheDir.'/display/old2.png', time() - 200);

        file_put_contents($this->tempCacheDir.'/display/new.png', str_repeat('b', 100 * 1024));
        touch($this->tempCacheDir.'/display/new.png', time());

        $freed = $this->service->purgeToLimit();

        $this->assertGreaterThan(0, $freed);
        $this->assertFileDoesNotExist($this->tempCacheDir.'/display/old1.png');
        $this->assertFileExists($this->tempCacheDir.'/display/new.png');
    }

    public function test_warm_cached_generates_and_purges(): void
    {
        config(['filesystems.image_cache.max_size_mb' => 100]);

        $media = $this->createImageMedia('images/photo.jpg', 1200, 800);

        $tempFile = tempnam(sys_get_temp_dir(), 'test-');
        file_put_contents($tempFile, Storage::disk('public')->get('images/photo.jpg'));

        try {
            $result = $this->service->warmCached($media, ImageCacheService::TIER_DISPLAY, $tempFile);

            $this->assertTrue($result);
            $path = $this->service->relativePath($media, ImageCacheService::TIER_DISPLAY);
            $this->assertFileExists($this->tempCacheDir.'/'.$path);
        } finally {
            @unlink($tempFile);
        }
    }

    public function test_warm_cached_returns_false_for_unknown_tier(): void
    {
        $media = $this->createImageMedia('images/photo.jpg');

        $result = $this->service->warmCached($media, 'nonexistent', '/tmp/any');

        $this->assertFalse($result);
    }

    public function test_warm_cached_returns_false_for_non_image(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'docs/file.txt',
            'mime_type' => 'text/plain',
        ]);

        $result = $this->service->warmCached($media, ImageCacheService::TIER_DISPLAY, '/tmp/any');

        $this->assertFalse($result);
    }

    public function test_tiers_returns_configured_values(): void
    {
        config(['filesystems.image_cache.tiers' => ['small' => 400, 'large' => 1200]]);

        $service = new ImageCacheService;
        $tiers = $service->tiers();

        $this->assertSame(['small' => 400, 'large' => 1200], $tiers);
    }

    public function test_tiers_defaults_to_display_and_lightbox(): void
    {
        config(['filesystems.image_cache' => []]);

        $service = new ImageCacheService;
        $tiers = $service->tiers();

        $this->assertArrayHasKey('display', $tiers);
        $this->assertArrayHasKey('lightbox', $tiers);
        $this->assertSame(800, $tiers['display']);
        $this->assertSame(1600, $tiers['lightbox']);
    }

    public function test_generate_returns_false_when_original_missing(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'images/nonexistent.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $result = $this->service->generate($media, ImageCacheService::TIER_DISPLAY);

        $this->assertFalse($result);
    }

    public function test_generate_returns_false_for_non_image(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'file_path' => 'docs/file.txt',
            'mime_type' => 'text/plain',
        ]);

        $result = $this->service->generate($media, ImageCacheService::TIER_DISPLAY);

        $this->assertFalse($result);
    }

    public function test_total_size_returns_zero_for_empty_cache(): void
    {
        $this->assertSame(0, $this->service->totalSize());
    }

    public function test_total_size_sums_file_sizes(): void
    {
        file_put_contents($this->tempCacheDir.'/display/a.png', str_repeat('a', 100));
        file_put_contents($this->tempCacheDir.'/lightbox/b.png', str_repeat('b', 200));

        $this->assertSame(300, $this->service->totalSize());
    }

    public function test_clear_removes_all_content(): void
    {
        file_put_contents($this->tempCacheDir.'/display/a.png', 'data');
        file_put_contents($this->tempCacheDir.'/lightbox/b.png', 'data');

        $this->service->clear();

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempCacheDir, \FilesystemIterator::SKIP_DOTS),
        );
        $this->assertCount(0, $files);
    }

    protected function createImageMedia(string $path, int $width = 800, int $height = 600): Media
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 120, 40, 200));

        ob_start();
        imagejpeg($image, quality: 85);
        imagedestroy($image);
        $bytes = (string) ob_get_clean();

        Storage::disk('public')->put($path, $bytes);

        return Media::query()->create([
            'disk' => 'public',
            'file_path' => $path,
            'mime_type' => 'image/jpeg',
            'width' => $width,
            'height' => $height,
            'file_size' => strlen($bytes),
        ]);
    }
}
