<?php

namespace App\Filament\Resources\ArTargets\Pages;

use App\Filament\Resources\ArTargets\ArTargetResource;
use App\Filament\Resources\ArTargets\Pages\Concerns\HandlesCompiledTarget;
use App\Models\ArTarget;
use App\Services\ArTargetFileService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditArTarget extends EditRecord
{
    use HandlesCompiledTarget;

    protected static string $resource = ArTargetResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $compiled = $this->validatedCompiledTarget(required: false);

        $record->update($data);

        if ($compiled) {
            app(ArTargetFileService::class)->storeCompiled(
                $record,
                $compiled['marker_upload'],
                $compiled['mind_upload'],
                $compiled['marker_width'],
                $compiled['marker_height'],
                $compiled['feature_points'],
            );
        }

        $this->forgetCompiledTarget();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateToken')
                ->label('Перевыпустить ссылку')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Перевыпустить публичную ссылку?')
                ->modalDescription('Старая ссылка и уже напечатанные QR-коды перестанут работать. Используйте только если ссылка попала не к тем людям.')
                ->modalSubmitActionLabel('Перевыпустить')
                ->action(function (ArTarget $record) {
                    $record->regenerateToken();
                    $this->refreshFormData(['public_url']);
                    Notification::make()->title('Ссылка перевыпущена')->success()->send();
                }),
            DeleteAction::make(),
        ];
    }
}
