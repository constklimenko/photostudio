<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $video->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .player {
            position: relative;
            width: min(100vw, calc(100vh * 16 / 9));
            aspect-ratio: 16 / 9;
            max-height: 100vh;
            overflow: hidden;
            background: #000;
        }
        .player.is-vertical {
            width: min(56.25vh, 100vw);
            aspect-ratio: 9 / 16;
        }
    </style>
</head>
<body>
    <div class="player {{ $video->is_upload && ! $video->rotate_90 && $video->type === 'vertical' ? 'is-vertical' : '' }}">
        <x-site.video-player :video="$video" />
    </div>
</body>
</html>
