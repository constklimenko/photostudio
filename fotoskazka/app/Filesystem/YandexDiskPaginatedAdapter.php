<?php

namespace App\Filesystem;

use ImpressiveWeb\Flysystem\YandexDiskAdapter as BaseYandexDiskAdapter;
use ImpressiveWeb\YandexDisk\Client;
use League\MimeTypeDetection\MimeTypeDetector;

class YandexDiskPaginatedAdapter extends BaseYandexDiskAdapter
{
    public const PAGE_SIZE = 100;

    protected readonly Client $apiClient;

    public function __construct(
        Client $client,
        ?MimeTypeDetector $mimeTypeDetector = null,
    ) {
        parent::__construct($client, $mimeTypeDetector);

        $this->apiClient = $client;
    }

    /**
     * Листинг с пагинацией: API Яндекс.Диска отдаёт страницу за запросом,
     * базовая реализация читает только первую (по умолчанию 20 элементов).
     */
    protected function iterateFolderContents(string $path, bool $deep): \Generator
    {
        if ($deep) {
            yield from parent::iterateFolderContents($path, true);

            return;
        }

        $fields = ['_embedded.items.path,_embedded.items.type,_embedded.total'];
        $offset = 0;

        do {
            $data = $this->apiClient->listContent($path, $fields, limit: self::PAGE_SIZE, offset: $offset);

            $items = $data['_embedded']['items'] ?? [];
            $total = (int) ($data['_embedded']['total'] ?? 0);
            $received = count($items);

            yield from $items;
            $offset += $received;

            $hasMore = $received > 0
                && ($total > 0 ? $offset < $total : $received === self::PAGE_SIZE);
        } while ($hasMore);
    }
}
