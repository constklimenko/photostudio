<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $metaDescription = trim($__env->yieldContent('meta_description', e(config('app.name'))));
        $metaDescription = e(Str::of(strip_tags(html_entity_decode($metaDescription, ENT_QUOTES)))->squish()->limit(160, '…'));
        $canonicalUrl = trim($__env->yieldContent('canonical')) ?: e(url()->current());
    @endphp

    <meta name="description" content="{!! $metaDescription !!}">
    <link rel="canonical" href="{!! $canonicalUrl !!}">

    @php
        $ogLocale = str_replace('-', '_', app()->getLocale());
        $ogLocale = str_contains($ogLocale, '_') ? $ogLocale : $ogLocale.'_'.strtoupper($ogLocale);
        $ogTitle = trim($__env->yieldContent('og_title')) ?: trim($__env->yieldContent('title', config('app.name')));
        $ogDescription = trim($__env->yieldContent('og_description')) ?: $metaDescription;
        $ogImage = trim($__env->yieldContent('og_image')) ?: e(url('web-app-manifest-512x512.png'));
        $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
        $ogUrl = trim($__env->yieldContent('og_url')) ?: $canonicalUrl;
    @endphp

    <meta property="og:type" content="{!! $ogType !!}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta property="og:title" content="{!! $ogTitle !!}">
    <meta property="og:description" content="{!! $ogDescription !!}">
    <meta property="og:url" content="{!! $ogUrl !!}">
    <meta property="og:image" content="{!! $ogImage !!}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{!! $ogTitle !!}">
    <meta name="twitter:description" content="{!! $ogDescription !!}">
    <meta name="twitter:image" content="{!! $ogImage !!}">

    <title>@yield('title', config('app.name'))</title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#ffffff">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="font-sans antialiased text-gray-300 bg-[#0a0a0a]">
    <x-site.header />

    <main>
        @yield('content')
    </main>

    <x-site.footer />

    <x-site.inquiry-modal :services="$serviceList" />
</body>
</html>
