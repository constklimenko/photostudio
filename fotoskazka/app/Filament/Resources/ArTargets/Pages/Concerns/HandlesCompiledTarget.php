<?php

namespace App\Filament\Resources\ArTargets\Pages\Concerns;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * Маркер и .mind приходят не из полей формы, а загрузкой из браузерного
 * компилятора ($wire.upload в data.marker_upload / data.mind_upload),
 * вместе с размерами маркера и числом опорных точек.
 */
trait HandlesCompiledTarget
{
    private const COMPILED_KEYS = ['marker_upload', 'mind_upload', 'marker_width', 'marker_height', 'feature_points'];

    /**
     * @return array{marker_upload: UploadedFile, mind_upload: UploadedFile, marker_width: int, marker_height: int, feature_points: ?int}|null
     */
    protected function validatedCompiledTarget(bool $required): ?array
    {
        $data = $this->data ?? [];

        if (! $required && empty($data['mind_upload']) && empty($data['marker_upload'])) {
            return null;
        }

        $validated = Validator::make(
            ['data' => array_intersect_key($data, array_flip(self::COMPILED_KEYS))],
            [
                'data.mind_upload' => ['required', 'file', 'max:20480', function (string $attribute, mixed $value, Closure $fail) {
                    if ($value instanceof UploadedFile && strtolower($value->getClientOriginalExtension()) !== 'mind') {
                        $fail('Файл таргета должен быть .mind — скомпилируйте фото заново.');
                    }
                }],
                'data.marker_upload' => ['required', 'image', 'mimes:jpeg,png', 'max:10240'],
                'data.marker_width' => ['required', 'integer', 'min:100', 'max:10000'],
                'data.marker_height' => ['required', 'integer', 'min:100', 'max:10000'],
                'data.feature_points' => ['nullable', 'integer', 'min:0'],
            ],
            [
                'data.mind_upload.required' => 'Выберите фото и дождитесь окончания компиляции таргета.',
            ],
            [
                'data.mind_upload' => 'файл таргета',
                'data.marker_upload' => 'фото-маркер',
                'data.marker_width' => 'ширина маркера',
                'data.marker_height' => 'высота маркера',
            ],
        )->validate()['data'];

        return [
            'marker_upload' => $validated['marker_upload'],
            'mind_upload' => $validated['mind_upload'],
            'marker_width' => (int) $validated['marker_width'],
            'marker_height' => (int) $validated['marker_height'],
            'feature_points' => isset($validated['feature_points']) ? (int) $validated['feature_points'] : null,
        ];
    }

    protected function forgetCompiledTarget(): void
    {
        foreach (self::COMPILED_KEYS as $key) {
            unset($this->data[$key]);
        }
    }
}
