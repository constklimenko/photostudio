<?php

namespace App\Filament\Resources\Media\Pages;

use App\Actions\Media\DeleteMedia;
use App\Filament\Resources\Media\MediaResource;
use App\Models\Media;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('Удаление медиа')
                ->modalDescription(fn (Media $record): string => $this->deleteDescription($record))
                ->form(fn (Media $record): array => $this->deleteForm($record))
                ->using(function (Media $record, array $data): bool {
                    return app(DeleteMedia::class)->execute(
                        $record,
                        deleteRemoteOriginal: (bool) ($data['delete_remote_original'] ?? false),
                    );
                })
                ->successNotificationTitle('Медиа удалено')
                ->failureNotificationTitle('Не удалось удалить оригинал файла. Запись сохранена, подробности — в журнале ошибок.'),
        ];
    }

    protected function deleteDescription(Media $record): string
    {
        if ($record->isRemoteDisk((string) $record->disk)) {
            return 'Запись и локальные производные (превью, кэш) будут удалены. Оригинал находится на Яндекс-Диске — выберите ниже, удалять ли его.';
        }

        return 'Будут удалены запись и все файлы: оригинал, превью и кэш. Действие необратимо.';
    }

    protected function deleteForm(Media $record): array
    {
        if (! $record->isRemoteDisk((string) $record->disk)) {
            return [];
        }

        return [
            Toggle::make('delete_remote_original')
                ->label('Удалить файл с Яндекс-Диска?')
                ->helperText('Если выключить, оригинал останется на Яндекс-Диске как потенциальный осиротевший файл.')
                ->default(false),
        ];
    }
}
