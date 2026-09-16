<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFacade;

class SitemapController extends Controller
{
    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /cabinet',
            'Disallow: /login',
            'Disallow: /logout',
            'Disallow: /media/*/original',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ])."\n";

        return ResponseFacade::make($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(SitemapService $sitemap): Response
    {
        return ResponseFacade::make($sitemap->render(), 200, ['Content-Type' => 'application/xml']);
    }
}
