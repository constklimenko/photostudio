<?php

namespace App\Providers;

use App\Services\PageContentService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        try {
            $pageContent = app(PageContentService::class);
            $menuItems = $pageContent->getMenuItems();
        } catch (\Throwable) {
            $menuItems = [];
        }

        View::share('menuItems', $menuItems);
    }
}
