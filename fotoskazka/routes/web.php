<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CabinetController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ServiceCatalogController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::controller(ServiceCatalogController::class)->prefix('services')->name('services.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{path}', 'show')->where('path', '.*')->name('show');
});

Route::controller(PortfolioController::class)->prefix('portfolio')->name('portfolio.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{slug}', 'show')->name('show');
});

Route::controller(BlogController::class)->prefix('blog')->name('blog.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{slug}', 'show')->name('show');
});

Route::get('/video', [VideoController::class, 'index'])->name('video.index');
Route::get('/video/{video}/stream', [VideoController::class, 'stream'])->name('video.stream');

Route::get('/media/{media}/original', [MediaController::class, 'original'])
    ->name('media.original');
Route::get('/media/{media}/thumbnail', [MediaController::class, 'thumbnail'])
    ->name('media.thumbnail');
Route::get('/media/{media}/download', [MediaController::class, 'download'])
    ->middleware('auth')
    ->name('media.download');
Route::get('/media/{media}/display', [MediaController::class, 'display'])
    ->name('media.display');
Route::get('/media/{media}/lightbox', [MediaController::class, 'lightbox'])
    ->name('media.lightbox');

Route::post('/inquiry', [HomeController::class, 'storeInquiry'])->name('inquiry.store');

Route::middleware('auth')->group(function () {
    Route::get('/cabinet', [CabinetController::class, 'index'])
        ->name('cabinet.index');

    Route::get('/cabinet/projects', [CabinetController::class, 'projects'])
        ->name('cabinet.projects');

    Route::get('/cabinet/projects/{project}', [CabinetController::class, 'show'])
        ->name('cabinet.project');

    Route::get('/cabinet/albums/{album}', [CabinetController::class, 'showAlbum'])
        ->name('cabinet.album');
});

require __DIR__.'/auth.php';
