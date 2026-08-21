<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', config('app.name'))">

    <title>@yield('title', config('app.name'))</title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

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
