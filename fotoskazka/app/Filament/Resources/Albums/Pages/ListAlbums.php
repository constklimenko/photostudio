<?php

namespace App\Filament\Resources\Albums\Pages;

use App\Filament\Resources\Albums\AlbumResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAlbums extends ListRecords
{
    protected static string $resource = AlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('upload')
                ->label('Загрузить фотографии')
                ->url(AlbumResource::getUrl('upload'))
                ->icon('heroicon-m-cloud-arrow-up')
                ->color('success'),
        ];
    }
}
