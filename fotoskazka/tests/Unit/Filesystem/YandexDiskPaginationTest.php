<?php

namespace Tests\Unit\Filesystem;

use App\Filesystem\YandexDiskPaginatedAdapter;
use ImpressiveWeb\YandexDisk\Client;
use League\Flysystem\Filesystem as Flysystem;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class YandexDiskPaginationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_listing_follows_api_pagination_until_total(): void
    {
        $page1 = $this->items('disk:/japanki', 0, 100);
        $page2 = $this->items('disk:/japanki', 100, 153);

        $client = $this->mockClient();
        $pageSize = YandexDiskPaginatedAdapter::PAGE_SIZE;

        $client->shouldReceive('listContent')
            ->once()->ordered()
            ->with('japanki', Mockery::on(fn ($f): bool => str_contains((string) $f[0], '_embedded.total')), $pageSize, 0)
            ->andReturn(['_embedded' => ['items' => $page1, 'total' => 153]]);
        $client->shouldReceive('listContent')
            ->once()->ordered()
            ->with('japanki', Mockery::any(), $pageSize, 100)
            ->andReturn(['_embedded' => ['items' => $page2, 'total' => 153]]);

        $paths = $this->listPaths($client);

        $this->assertCount(153, $paths);
        $this->assertSame('japanki/img-000.jpg', $paths[0]);
        $this->assertSame('japanki/img-152.jpg', $paths[152]);
    }

    public function test_single_page_does_not_trigger_extra_request(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('listContent')
            ->once()
            ->andReturn(['_embedded' => [
                'items' => $this->items('disk:/album', 0, 7),
                'total' => 7,
            ]]);

        $this->assertCount(7, $this->listPaths($client, 'album'));
    }

    public function test_stops_when_total_is_missing_and_page_is_partial(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('listContent')
            ->once()
            ->andReturn(['_embedded' => ['items' => $this->items('disk:/album', 0, 5)]]);

        $this->assertCount(5, $this->listPaths($client, 'album'));
    }

    public function test_continues_without_total_while_page_is_full(): void
    {
        $pageSize = YandexDiskPaginatedAdapter::PAGE_SIZE;

        $client = $this->mockClient();
        $client->shouldReceive('listContent')
            ->once()->ordered()
            ->andReturn(['_embedded' => ['items' => $this->items('disk:/album', 0, $pageSize)]]);
        $client->shouldReceive('listContent')
            ->once()->ordered()
            ->with(Mockery::any(), Mockery::any(), $pageSize, $pageSize)
            ->andReturn(['_embedded' => ['items' => []]]);

        $this->assertCount($pageSize, $this->listPaths($client, 'album'));
    }

    protected function listPaths(Client $client, string $folder = 'japanki'): array
    {
        $filesystem = new Flysystem(new YandexDiskPaginatedAdapter($client), []);

        return collect($filesystem->listContents($folder, false))
            ->map(fn (object $item): string => $item->path())
            ->values()
            ->all();
    }

    protected function items(string $prefix, int $from, int $to): array
    {
        $items = [];

        for ($i = $from; $i < $to; $i++) {
            $items[] = [
                'path' => sprintf('%s/img-%03d.jpg', $prefix, $i),
                'type' => 'file',
            ];
        }

        return $items;
    }

    protected function mockClient(): Client&MockInterface
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getPathPrefix')->andReturn('disk:/');

        return $client;
    }
}
