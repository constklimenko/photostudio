<?php

namespace App\Filament\Resources\Icons\Pages;

use App\Filament\Resources\Icons\IconResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIcons extends ListRecords
{
    protected static string $resource = IconResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
