<?php

namespace App\Filament\Resources\Media\Tables;

use App\Actions\Media\DeleteMedia;
use App\Models\Media;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Toggle;
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::deleteBulkAction(),
                ]),
            ]);
    }

    protected static function deleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->modalHeading('Удалить выбранные медиа?')
            ->modalDescription(function (EloquentCollection|Collection|LazyCollection $records): string {
                $total = $records->count();
                $remoteCount = self::countRemoteRecords($records);

                if ($remoteCount === 0) {
                    return "Выбрано файлов: {$total}. Будут удалены записи и все локальные файлы.";
                }

                return "Выбрано файлов: {$total}. В выбранных элементах находятся {$remoteCount} оригиналов на Яндекс-Диске.";
            })
            ->form([
                Toggle::make('delete_remote_original')
                    ->label('Удалить эти файлы с Яндекс-Диска?')
                    ->helperText('Если выключить, оригиналы останутся на Яндекс-Диске как потенциальные осиротевшие файлы.')
                    ->default(false)
                    ->hidden(fn (Get $get): bool => ! ((bool) $get('__has_remote_originals'))),
                Toggle::make('__has_remote_originals')
                    ->default(false)
                    ->hidden()
                    ->dehydrated(false),
            ])
            ->mountUsing(function (Schema $schema, EloquentCollection|Collection|LazyCollection $records): void {
                $schema->fill([
                    '__has_remote_originals' => self::countRemoteRecords($records) > 0,
                    'delete_remote_original' => false,
                ]);
            })
            ->using(function (
                DeleteBulkAction $action,
                EloquentCollection|Collection|LazyCollection $records,
                array $data,
            ): void {
                $deleteRemoteOriginal = (bool) ($data['delete_remote_original'] ?? false);
                $deleteMedia = app(DeleteMedia::class);

                foreach ($records as $media) {
                    if (! $deleteMedia->execute($media, deleteRemoteOriginal: $deleteRemoteOriginal)) {
                        $action->reportBulkProcessingFailure();
                    }
                }
            })
            ->successNotificationTitle('Выбранные медиа удалены')
            ->failureNotificationTitle(function (int $successCount, int $totalCount): string {
                if ($successCount > 0) {
                    return "Удалено файлов: {$successCount} из {$totalCount}. Остальные записи сохранены — оригинал не удалось удалить.";
                }

                return "Не удалось удалить файлы ({$totalCount}). Записи сохранены — подробности в журнале ошибок.";
            });
    }

    protected static function countRemoteRecords(EloquentCollection|Collection|LazyCollection $records): int
    {
        return $records->filter(
            fn (Media $media): bool => $media->isRemoteDisk((string) $media->disk),
        )->count();
    }
}
