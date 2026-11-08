@extends('ar.layout')

@section('styles')
    body { overflow: hidden; }
    #ar { position: fixed; inset: 0; z-index: 0; isolation: isolate; }
    #ar video { object-fit: cover; }
    .hud { position: fixed; inset: 0; z-index: 10; display: flex; flex-direction: column; justify-content: space-between;
        padding: max(16px, env(safe-area-inset-top)) 16px max(20px, env(safe-area-inset-bottom)); pointer-events: none; }
    .hud > * { pointer-events: auto; }
    .top { display: flex; justify-content: center; text-shadow: 0 1px 3px rgba(0, 0, 0, .6); }
    .bottom { display: flex; flex-direction: column; align-items: center; gap: 12px; }
    .hint { padding: 10px 16px; border-radius: 999px; background: rgba(0, 0, 0, .55); }
    .status.err { color: var(--err); }
@endsection

@section('content')
    <div id="ar"></div>

    <div class="hud">
        <div class="top"><span class="brand">Фотосказка</span></div>

        <div class="card" id="intro">
            <h1>Оживающее фото</h1>
            <p>Нажмите «Старт», разрешите доступ к камере и наведите её на фото в альбоме.</p>
            <button class="btn" id="start" type="button">Старт</button>
            <p class="status" id="status" hidden></p>
        </div>

        <div class="bottom">
            <div class="hint" id="hint" hidden>Наведите камеру на фото</div>
            <button class="btn secondary" id="sound" type="button" hidden>🔊 Включить звук</button>
        </div>
    </div>

    <script type="application/json" id="ar-config">@json($config)</script>
    <script type="importmap">
        {
            "imports": {
                "three": "{{ asset('vendor/mindar/1.2.5/three.module.js') }}",
                "three/addons/renderers/CSS3DRenderer.js": "{{ asset('vendor/mindar/1.2.5/three-addons/renderers/CSS3DRenderer.js') }}"
            }
        }
    </script>
    <script type="module" src="{{ asset('js/ar/ar-viewer.js') }}?v=20261027"></script>
@endsection
