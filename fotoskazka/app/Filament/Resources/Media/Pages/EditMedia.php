<?php

namespace App\Filament\Resources\Media\Pages;

use App\Actions\Media\DeleteMedia;
use App\Filament\Resources\Media\MediaResource;
use App\Jobs\ProcessMedia;
use App\Models\Media;
use App\Services\MediaProcessor;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getDeleteMediaAction(),
            $this->getRetryProcessingAction(),
        ];
    }

    protected function getDeleteMediaAction(): DeleteAction
    {
        $action = DeleteAction::make()
            ->modalHeading('Удаление медиа')
            ->modalDescription(fn (Media $record): string => $this->deleteDescription($record))
            ->modalSubmitActionLabel(fn (Media $record): string => $record->isRemoteDisk((string) $record->disk)
                ? 'Удалить Media, оставить файл'
                : 'Удалить')
            ->using(function (Media $record, array $arguments): bool {
                return app(DeleteMedia::class)->execute(
                    $record,
                    deleteRemoteOriginal: (bool) ($arguments['delete_remote_original'] ?? false),
                );
            })
            ->successNotificationTitle('Медиа удалено')
            ->failureNotificationTitle('Не удалось удалить оригинал файла. Запись сохранена, подробности — в журнале ошибок.');

        $action->extraModalFooterActions(
            fn (Media $record): array => $record->isRemoteDisk((string) $record->disk) ? [
                $action->makeModalSubmitAction('deleteWithRemoteFile', arguments: ['delete_remote_original' => true])
                    ->label('Удалить Media и файл')
                    ->color('danger'),
            ] : [],
        );

        return $action;
    }

    protected function getRetryProcessingAction(): Action
    {
        return Action::make('retryProcessing')
            ->label('Повторить обработку')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (): bool => app(MediaProcessor::class)->isPending($this->getRecord()))
            ->requiresConfirmation()
            ->modalHeading('Повторная обработка')
            ->modalDescription('Будет заново заполнены метаданные, превью и кэш-версии. Файлы не удаляются.')
            ->action(function (): void {
                ProcessMedia::dispatch($this->getRecord()->getKey());

                Notification::make()
                    ->title('Повторная обработка запущена')
                    ->body('Результат появится после выполнения фонового задания.')
                    ->info()
                    ->send();
            });
    }

    protected function deleteDescription(Media $record): string
    {
        if ($record->isRemoteDisk((string) $record->disk)) {
            return 'Запись и локальные производные будут удалены. Удалить файл с Яндекс-Диска? '
                .'Если оставить файл, он останется на Диске без записи в базе.';
        }

        return 'Будут удалены запись и все файлы: оригинал, превью и кэш. Действие необратимо.';
    }
}
