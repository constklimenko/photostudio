@php
    $record = $getRecord();
    $containerPath = \Illuminate\Support\Str::beforeLast($getStatePath(), '.');
    $markerUrl = $record?->marker_path
        ? \Illuminate\Support\Facades\Storage::disk($record->disk)->url($record->marker_path)
        : null;
@endphp

{{-- Стили встроенные: у панели нет своей сборки Tailwind, утилиты вне Filament не компилируются. --}}
<div
    wire:ignore
    x-load
    x-load-src="{{ asset('js/ar/ar-marker-compiler.js') }}?v=20261027-2"
    x-data="arMarkerCompiler({
        compilerUrl: @js(asset('vendor/mindar/1.2.5/controller-mGt1s8dJ.js')),
        statePath: @js($containerPath),
        maxSide: 1600,
        weakPoints: {{ \App\Filament\Resources\ArTargets\Tables\ArTargetsTable::WEAK_FEATURE_POINTS }},
    })"
    style="display: grid; gap: 12px; font-size: 14px;"
>
    @if ($markerUrl)
        <img src="{{ $markerUrl }}" alt="" style="max-height: 220px; max-width: 100%; border-radius: 8px;">
        <div style="color: var(--gray-500); line-height: 1.6;">
            Маркер {{ $record->marker_width }}×{{ $record->marker_height }}
            · опорных точек: {{ $record->feature_points ?? '—' }}
            · собран {{ $record->compiled_at?->format('d.m.Y H:i') ?? '—' }}
        </div>
        <div style="color: var(--warning-600);">
            Замена фото пересоберёт таргет: ссылка останется прежней, но уже напечатанные
            отпечатки старого фото перестанут распознаваться.
        </div>
    @endif

    <label style="display: grid; gap: 6px;">
        <span style="font-weight: 500;">{{ $markerUrl ? 'Заменить фото' : 'Фото для распознавания (JPEG или PNG)' }}</span>
        <input
            type="file"
            accept="image/jpeg,image/png"
            x-on:change="compile($event.target.files[0])"
            x-bind:disabled="busy"
        >
    </label>

    <div x-show="busy || progress > 0" x-cloak style="height: 8px; border-radius: 4px; background: var(--gray-200); overflow: hidden;">
        <div x-bind:style="`height: 8px; width: ${progress}%; background: var(--primary-600); transition: width .2s;`"></div>
    </div>

    <div x-show="status" x-text="status" x-cloak></div>
    <div x-show="error" x-text="error" x-cloak style="color: var(--danger-600);"></div>

    <template x-if="result">
        <div style="display: grid; gap: 4px;">
            <div>
                Собрано за <span x-text="result.seconds"></span> с · маркер
                <span x-text="result.width"></span>×<span x-text="result.height"></span> ·
                <span x-text="result.kb"></span> КБ · опорных точек: <span x-text="result.points"></span>
            </div>
            <div x-show="result.points < weakPoints" style="color: var(--warning-600);">
                Мало опорных точек — фото может плохо распознаваться (однотонный фон, мало деталей).
                Сохранить можно, но проверьте на телефоне.
            </div>
        </div>
    </template>
</div>
