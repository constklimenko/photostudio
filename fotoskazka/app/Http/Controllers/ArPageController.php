<?php

namespace App\Http\Controllers;

use App\Models\ArTarget;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ArPageController extends Controller
{
    public function __invoke(string $token): Response
    {
        $target = ArTarget::query()->where('token', $token)->firstOrFail();

        if (! $target->is_active) {
            return $this->noindex(response()->view('ar.unavailable', [
                'heading' => 'Ссылка отключена',
                'message' => 'Эта ссылка больше не работает. Если вы считаете, что это ошибка, свяжитесь с фотостудией.',
            ], 410));
        }

        $content = $target->activeContents()->first();

        if (! $target->target_path || ! $content) {
            return $this->noindex(response()->view('ar.unavailable', [
                'heading' => 'Видео ещё не загружено',
                'message' => 'Фотография скоро оживёт — загляните сюда чуть позже.',
            ]));
        }

        return $this->noindex(response()->view('ar.show', [
            'config' => [
                'target' => Storage::disk($target->disk)->url($target->target_path),
                'video' => Storage::disk($content->disk)->url($content->file_path),
                'aspect' => $target->markerAspect(),
                'hasSound' => $content->has_sound,
            ],
        ]));
    }

    private function noindex(Response $response): Response
    {
        return $response->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
