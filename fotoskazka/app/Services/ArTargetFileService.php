<?php

namespace App\Services;

use App\Models\ArTarget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArTargetFileService
{
    public const TRACKER = 'mindar';

    public const TRACKER_VERSION = '1.2.5';

    /**
     * Сохраняет маркер и скомпилированный в браузере .mind таргета.
     * Имена файлов уникальны на каждую компиляцию — CDN не отдаст старую копию;
     * прежние файлы удаляются после успешной записи новых.
     */
    public function storeCompiled(
        ArTarget $target,
        UploadedFile $marker,
        UploadedFile $mind,
        int $markerWidth,
        int $markerHeight,
        ?int $featurePoints,
    ): void {
        $previous = array_filter([$target->marker_path, $target->target_path]);

        $directory = "ar/targets/{$target->id}";
        $stamp = now()->format('YmdHis').'-'.Str::lower(Str::random(6));
        $extension = $marker->getMimeType() === 'image/png' ? 'png' : 'jpg';

        $markerPath = $marker->storeAs($directory, "marker-{$stamp}.{$extension}", $target->disk);
        $targetPath = $mind->storeAs($directory, "target-{$stamp}.mind", $target->disk);

        $target->forceFill([
            'marker_path' => $markerPath,
            'marker_width' => $markerWidth,
            'marker_height' => $markerHeight,
            'tracker' => self::TRACKER,
            'tracker_version' => self::TRACKER_VERSION,
            'target_path' => $targetPath,
            'feature_points' => $featurePoints,
            'compiled_at' => now(),
        ])->save();

        Storage::disk($target->disk)->delete(array_diff($previous, [$markerPath, $targetPath]));
    }
}
