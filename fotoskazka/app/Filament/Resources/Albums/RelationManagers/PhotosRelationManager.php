<?php

namespace App\Filament\Resources\Albums\RelationManagers;

use App\Actions\Media\RotateMedia;
use App\Models\Album;
use App\Models\Photo;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Фотографии';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('media.thumbnail_path')
                    ->label('Превью')
                    ->disk('thumbnails')
                    ->square()
                    ->size(80),
                TextColumn::make('media.title')
                    ->label('Название')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('caption')
                    ->label('Подпись')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                $this->addFromAlbumAction(),
            ])
            ->recordActions([
                Action::make('setCover')
                    ->label('Сделать обложкой')
                    ->icon('heroicon-o-photo')
                    ->action(function ($record) {
                        $this->ownerRecord->update(['cover_media_id' => $record->media_id]);
                    }),
                $this->rotateAction(),
                $this->editAction(),
                Action::make('delete')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->delete()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
    }

    protected function rotateAction(): Action
    {
        return Action::make('rotate')
            ->label('Повернуть')
            ->icon('heroicon-o-arrow-path')
            ->form([
                Select::make('degrees')
                    ->label('Угол поворота')
                    ->options([
                        '90' => '90° по часовой',
                        '180' => '180°',
                        '270' => '90° против часовой',
                    ])
                    ->default('90')
                    ->required(),
            ])
            ->action(function (array $data, Photo $record): void {
                $media = $record->media;

                if (! $media) {
                    Notification::make()->title('Фото без файла')->danger()->send();

                    return;
                }

                $success = app(RotateMedia::class)->execute($media, (int) $data['degrees']);

                $notification = Notification::make()
                    ->title($success ? 'Фото повёрнуто' : 'Не удалось повернуть фото');

                if ($success) {
                    $notification->success();
                } else {
                    $notification->danger();
                }

                $notification->send();
            });
    }

    protected function editAction(): Action
    {
        return Action::make('edit')
            ->label('Редактировать')
            ->icon('heroicon-o-pencil')
            ->modalHeading('Редактировать фотографию')
            ->form([
                TextInput::make('media.title')->label('Название')->maxLength(255),
                TextInput::make('caption')->label('Подпись')->maxLength(255),
                TextInput::make('sort_order')->label('Порядок')->integer()->default(0),
            ])
            ->fillForm(fn (Photo $record): array => [
                'media' => ['title' => $record->media?->title],
                'caption' => $record->caption,
                'sort_order' => $record->sort_order,
            ])
            ->action(function (array $data, Photo $record): void {
                $record->update([
                    'caption' => $data['caption'] ?? null,
                    'sort_order' => $data['sort_order'] ?? 0,
                ]);

                if ($record->media) {
                    $record->media->update([
                        'title' => $data['media']['title'] ?? null,
                    ]);
                }
            });
    }

    protected function addFromAlbumAction(): Action
    {
        return Action::make('addFromAlbum')
            ->label('Добавить из альбома')
            ->icon('heroicon-o-plus-circle')
            ->modalHeading('Добавить фотографии из другого альбома')
            ->modalWidth('5xl')
            ->form([
                Select::make('source_album_id')
                    ->label('Альбом-источник')
                    ->options(fn (): array => Album::query()
                        ->where('id', '!=', $this->ownerRecord->id)
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->toArray())
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (Set $set) => $set('media_ids', [])),
                CheckboxList::make('media_ids')
                    ->label('Фотографии')
                    ->columns(3)
                    ->options(function (Get $get): array {
                        return $this->getSourceAlbumMediaOptions((int) $get('source_album_id'));
                    })
                    ->disabled(fn (Get $get): bool => blank($get('source_album_id')))
                    ->live(),
            ])
            ->action(function (array $data): void {
                $this->addPhotosFromAlbum($data);
            });
    }

    protected function getSourceAlbumMediaOptions(int $albumId): array
    {
        if (! $albumId) {
            return [];
        }

        $currentMediaIds = $this->ownerRecord->photos()
            ->pluck('media_id')
            ->toArray();

        return Photo::query()
            ->where('album_id', $albumId)
            ->with('media')
            ->get()
            ->filter(fn (Photo $photo): bool => $photo->media !== null && ! in_array($photo->media_id, $currentMediaIds, true))
            ->mapWithKeys(fn (Photo $photo): array => [
                $photo->media_id => $photo->media->title ?? basename((string) $photo->media->file_path),
            ])
            ->toArray();
    }

    protected function addPhotosFromAlbum(array $data): void
    {
        $albumId = (int) ($data['source_album_id'] ?? 0);
        $mediaIds = $data['media_ids'] ?? [];

        if (! $albumId || empty($mediaIds)) {
            Notification::make()->title('Не выбрано ни одной фотографии')->warning()->send();

            return;
        }

        $maxSort = $this->ownerRecord->photos()->max('sort_order') ?? 0;

        $existingMediaIds = $this->ownerRecord->photos()
            ->pluck('media_id')
            ->toArray();

        $newMediaIds = array_values(array_diff($mediaIds, $existingMediaIds));

        if (empty($newMediaIds)) {
            Notification::make()->title('Ничего нового — все выбранные фото уже в альбоме')->warning()->send();

            return;
        }

        foreach ($newMediaIds as $index => $mediaId) {
            Photo::create([
                'album_id' => $this->ownerRecord->id,
                'media_id' => $mediaId,
                'sort_order' => $maxSort + $index + 1,
            ]);
        }

        Notification::make()
            ->title('Добавлено фотографий: '.count($newMediaIds))
            ->success()
            ->send();
    }
}
