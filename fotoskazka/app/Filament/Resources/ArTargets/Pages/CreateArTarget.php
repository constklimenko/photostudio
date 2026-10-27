<?php

namespace App\Filament\Resources\ArTargets\Pages;

use App\Filament\Resources\ArTargets\ArTargetResource;
use App\Filament\Resources\ArTargets\Pages\Concerns\HandlesCompiledTarget;
use App\Models\ArTarget;
use App\Services\ArTargetFileService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateArTarget extends CreateRecord
{
    use HandlesCompiledTarget;

    protected static string $resource = ArTargetResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $compiled = $this->validatedCompiledTarget(required: true);

        return DB::transaction(function () use ($data, $compiled) {
            $target = ArTarget::create([
                ...$data,
                'marker_path' => '',
                'marker_width' => $compiled['marker_width'],
                'marker_height' => $compiled['marker_height'],
                'created_by' => auth()->id(),
            ]);

            app(ArTargetFileService::class)->storeCompiled(
                $target,
                $compiled['marker_upload'],
                $compiled['mind_upload'],
                $compiled['marker_width'],
                $compiled['marker_height'],
                $compiled['feature_points'],
            );

            return $target;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
