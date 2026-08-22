<?php

namespace App\Filament\Resources\Albums\Pages;

use App\Filament\Resources\Albums\AlbumResource;
use App\Models\Media;
use App\Models\Photo;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Config;

class EditAlbum extends EditRecord
{
    protected static string $resource = AlbumResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['_slug_manual'] = '1';

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getPhotoUploadAction(),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    public function getPhotoUploadAction(): Action
    {
        $defaultDisk = Config::get('filesystems.default_media_disk', 'public');

        return Action::make('uploadPhotos')
            ->label('Добавить фотографии')
            ->form([
                Grid::make(1)
                    ->schema([
                        FileUpload::make('photos')
                            ->label('Фотографии')
                            ->multiple()
                            ->image()
                            ->disk($defaultDisk)
                            ->visibility('public')
                            ->panelLayout('grid')
                            ->previewable(true)
                            ->reorderable()
                            ->appendFiles()
                            ->maxFiles(500)
                            ->maxSize(51200),
                    ]),
            ])
            ->action(function (array $data) use ($defaultDisk) {
                $album = $this->record;
                $lastSortOrder = $album->photos()->max('sort_order') ?? -1;

                foreach ($data['photos'] as $index => $photoPath) {
                    $media = Media::create([
                        'file_path' => $photoPath,
                        'disk' => $defaultDisk,
                        'collection' => 'gallery',
                        'title' => $album->title.' — '.($lastSortOrder + $index + 1),
                    ]);

                    Photo::create([
                        'album_id' => $album->id,
                        'media_id' => $media->id,
                        'sort_order' => $lastSortOrder + $index + 1,
                    ]);
                }
            });
    }
}
