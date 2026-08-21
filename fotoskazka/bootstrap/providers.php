<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ViewComposerServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ViewComposerServiceProvider::class,
];
