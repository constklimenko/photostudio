<?php

namespace Tests\Feature\Services;

use App\Models\Media;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Tests\TestCase;

class MediaProcessorRemoteStreamTest extends TestCase
{
    use RefreshDatabase;

    protected string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = storage_path('framework/testing/remote-like-'.uniqid());
        @mkdir($this->baseDir, 0777, true);

        config(['filesystems.disks.remote_like' => ['driver' => 'remote_like']]);
        Storage::forgetDisk('remote_like');

        $inner = new LocalFilesystemAdapter($this->baseDir);

        app('filesystem')->extend('remote_like', fn (): FilesystemAdapter => new FilesystemAdapter(
            new Flysystem(new PhpTempStreamAdapter($inner), []),
            new PhpTempStreamAdapter($inner),
            [],
        ));

        Storage::fake('thumbnails');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->baseDir)) {
            foreach (glob($this->baseDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->baseDir);
        }

        parent::tearDown();
    }

    public function test_fills_metadata_from_remote_style_stream(): void
    {
        Storage::disk('remote_like')->put('albums/photo.jpg', $this->makeJpegBytes(600, 300));

        $media = Media::query()->create([
            'album_id' => null,
            'disk' => 'remote_like',
            'file_path' => 'albums/photo.jpg',
        ]);

        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(600, $media->width);
        $this->assertSame(300, $media->height);
        $this->assertNotNull($media->thumbnail_path);
    }

    public function test_generates_webp_thumbnail_from_remote_style_stream(): void
    {
        Storage::disk('remote_like')->put('albums/photo.jpg', $this->makeJpegBytes(600, 300));

        $media = Media::query()->create([
            'album_id' => null,
            'disk' => 'remote_like',
            'file_path' => 'albums/photo.jpg',
        ]);

        $thumbDisk = Storage::disk('thumbnails');

        $this->assertTrue($thumbDisk->exists($media->thumbnail_path));
    }

    public function test_skips_thumbnail_for_non_image(): void
    {
        Storage::disk('remote_like')->put('docs/readme.txt', 'hello');

        $media = Media::query()->create([
            'album_id' => null,
            'disk' => 'remote_like',
            'file_path' => 'docs/readme.txt',
        ]);

        $this->assertSame('text/plain', $media->mime_type);
        $this->assertNull($media->thumbnail_path);
    }

    protected function makeJpegBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 120, 40, 200));

        ob_start();
        imagejpeg($image, quality: 85);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}

class PhpTempStreamAdapter implements FlysystemAdapter
{
    public function __construct(private readonly LocalFilesystemAdapter $inner) {}

    public function readStream(string $path)
    {
        $source = $this->inner->readStream($path);

        $temp = fopen('php://temp', 'r+b');

        stream_copy_to_stream($source, $temp);
        rewind($temp);
        fclose($source);

        return $temp;
    }

    public function fileExists(string $path): bool
    {
        return $this->inner->fileExists($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->inner->directoryExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->inner->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->inner->writeStream($path, $contents, $config);
    }

    public function read(string $path): string
    {
        return $this->inner->read($path);
    }

    public function delete(string $path): void
    {
        $this->inner->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->inner->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->inner->createDirectory($path, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->inner->setVisibility($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->inner->visibility($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->inner->mimeType($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->inner->lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->inner->fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return $this->inner->listContents($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->inner->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->inner->copy($source, $destination, $config);
    }
}
