<?php

namespace App\Observers;

use App\Models\Album;
use App\Models\Category;
use App\Models\Post;
use App\Models\Service;
use App\Services\SitemapService;

class SitemapCacheObserver
{
    public function saved(Category|Service|Post|Album $model): void
    {
        app(SitemapService::class)->clearCache();
    }

    public function deleted(Category|Service|Post|Album $model): void
    {
        app(SitemapService::class)->clearCache();
    }
}
