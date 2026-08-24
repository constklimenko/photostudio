<?php

namespace App\Filament\Resources\Media\Tables;

use App\Actions\Media\DeleteMedia;
use App\Jobs\ProcessMedia;
use App\Models\Media;
use App\Services\MediaProcessor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Collection;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\LazyCollection;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('collection')
                    ->badge()
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('file_size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 1).' KB' : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('disk')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('processing_state')
                    ->label('Обработка')
                    ->state(fn (Media $record): string => app(MediaProcessor::class)->isPending($record) ? 'pending' : 'ready')
                    ->formatStateUsing(fn (string $state): string => $state === 'ready' ? 'Готово' : 'В очереди')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'ready' ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('collection')
                    ->options([
                        'covers' => 'Обложки',
                        'gallery' => 'Галерея',
                        'avatars' => 'Аватары',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                self::retryProcessingAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::deleteBulkAction(),
                ]),
            ]);
    }

    protected static function retryProcessingAction(): Action
    {
        return Action::make('retryProcessing')
            ->label('Повторить обработку')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->visible(fn (Media $record): bool => app(MediaProcessor::class)->isPending($record))
            ->action(function (Media $record): void {
                ProcessMedia::dispatch($record->getKey());

                Notification::make()
                    ->title('Повторная обработка запущена')
                    ->body('Результат появится после выполнения фонового задания.')
                    ->info()
                    ->send();
            });
    }

    protected static function deleteBulkAction(): DeleteBulkAction
    {
        $stats = [
            'deleted_local' => 0,
            'deleted_with_remote_file' => 0,
            'deleted_keeping_remote_file' => 0,
            'failed' => 0,
        ];

        return DeleteBulkAction::make()
            ->modalHeading('Удалить выбранные медиа?')
            ->modalDescription(function (EloquentCollection|Collection|LazyCollection $records): string {
                $total = $records->count();
                $remoteCount = self::countRemoteRecords($records);

                if ($remoteCount === 0) {
                    return "Выбрано файлов: {$total}. Будут удалены записи и все локальные файлы.";
                }

                return "Выбрано файлов: {$total}. В выбранных элементах находятся {$remoteCount} оригиналов на Яндекс-Диске. Удалить эти файлы с Яндекс-Диска?";
            })
            ->form([
                Radio::make('delete_remote_original')
                    ->label('Удалить эти файлы с Яндекс-Диска?')
                    ->options([
                        '0' => 'Нет, оставить файлы на Яндекс-Диске',
                        '1' => 'Да, удалить файлы с Яндекс-Диска',
                    ])
                    ->default('0')
                    ->columnSpanFull()
                    ->hidden(fn (Get $get): bool => ! ((bool) $get('__has_remote_originals'))),
                Toggle::make('__has_remote_originals')
                    ->default(false)
                    ->hidden()
                    ->dehydrated(false),
            ])
            ->mountUsing(function (Schema $schema, EloquentCollection|Collection|LazyCollection $records): void {
                $schema->fill([
                    '__has_remote_originals' => self::countRemoteRecords($records) > 0,
                    'delete_remote_original' => '0',
                ]);
            })
            ->using(function (
                DeleteBulkAction $action,
                EloquentCollection|Collection|LazyCollection $records,
                array $data,
            ) use (&$stats): void {
                $deleteRemoteOriginal = (bool) ($data['delete_remote_original'] ?? false);
                $deleteMedia = app(DeleteMedia::class);

                foreach ($stats as $key => $value) {
                    $stats[$key] = 0;
                }

                foreach ($records as $media) {
                    $isRemote = $media->isRemoteDisk((string) $media->disk);

                    if (! $deleteMedia->execute($media, deleteRemoteOriginal: $deleteRemoteOriginal)) {
                        $stats['failed']++;
                        $action->reportBulkProcessingFailure();

                        continue;
                    }

                    if ($isRemote) {
                        $stats[$deleteRemoteOriginal ? 'deleted_with_remote_file' : 'deleted_keeping_remote_file']++;
                    } else {
                        $stats['deleted_local']++;
                    }
                }
            })
            ->successNotification(function (Notification $notification) use (&$stats): Notification {
                return $notification
                    ->title('Выбранные медиа удалены')
                    ->body(implode("\n", self::buildStatsBody($stats)));
            })
            ->failureNotification(function (Notification $notification, int $successCount, int $totalCount) use (&$stats): Notification {
                $lines = self::buildStatsBody($stats);
                $lines[] = "Не удалено из-за ошибки: {$stats['failed']} (записи сохранены, подробности в журнале ошибок).";

                return $notification
                    ->title("Удалено файлов: {$successCount} из {$totalCount}")
                    ->body(implode("\n", $lines));
            });
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string>
     */
    protected static function buildStatsBody(array $stats): array
    {
        $deletedTotal = $stats['deleted_local']
            + $stats['deleted_with_remote_file']
            + $stats['deleted_keeping_remote_file'];

        $lines = ["Удалено файлов: {$deletedTotal}."];

        if ($stats['deleted_with_remote_file'] > 0) {
            $lines[] = "Вместе с оригиналами на Яндекс-Диске: {$stats['deleted_with_remote_file']}.";
        }

        if ($stats['deleted_keeping_remote_file'] > 0) {
            $lines[] = "С сохранением оригиналов на Яндекс-Диске: {$stats['deleted_keeping_remote_file']}.";
        }

        return $lines;
    }

    protected static function countRemoteRecords(EloquentCollection|Collection|LazyCollection $records): int
    {
        return $records->filter(
            fn (Media $media): bool => $media->isRemoteDisk((string) $media->disk),
        )->count();
    }
}
