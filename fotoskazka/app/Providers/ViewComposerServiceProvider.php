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
        View::composer('layouts.site', function ($view) {
            $pageContent = app(PageContentService::class);
            $view->with('menuItems', $pageContent->getMenuItems());
        });
    }
}
