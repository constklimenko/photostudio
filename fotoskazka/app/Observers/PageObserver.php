<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\PageContentService;

class PageObserver
{
    public function saved(Page $page): void
    {
        app(PageContentService::class)->clearCache($page->slug);
    }

    public function deleted(Page $page): void
    {
        app(PageContentService::class)->clearCache();
    }
}
