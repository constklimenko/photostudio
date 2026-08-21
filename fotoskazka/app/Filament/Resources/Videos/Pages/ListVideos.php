<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideosResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVideos extends ListRecords
{
    protected static string $resource = VideosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
