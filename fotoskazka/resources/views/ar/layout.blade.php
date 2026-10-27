<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Оживающее фото — Фотосказка</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root { --bg: #10131a; --fg: #f2f4f8; --dim: #a3abbd; --accent: #f5b400; --err: #ff8a80; }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; background: var(--bg); color: var(--fg);
            font: 16px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; overscroll-behavior: none; }
        .brand { font-weight: 600; letter-spacing: .02em; }
        .card { max-width: 420px; margin: 0 auto; padding: 20px; background: rgba(0, 0, 0, .55);
            border-radius: 16px; text-align: center; backdrop-filter: blur(6px); }
        .card h1 { margin: 0 0 8px; font-size: 22px; }
        .card p { margin: 0 0 14px; color: var(--dim); }
        .btn { appearance: none; border: 0; border-radius: 999px; padding: 14px 28px; font: inherit;
            font-weight: 600; background: var(--accent); color: #1c1600; cursor: pointer; }
        .btn:disabled { opacity: .5; cursor: default; }
        .btn.secondary { background: rgba(255, 255, 255, .92); color: #111; }
        [hidden] { display: none !important; }
        @yield('styles')
    </style>
</head>
<body>
@yield('content')
</body>
</html>
