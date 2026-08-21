<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideosResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVideo extends EditRecord
{
    protected static string $resource = VideosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
